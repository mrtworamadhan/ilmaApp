<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateBillsCommand extends Command
{
    protected $signature = 'app:generate-bills';
    protected $description = 'Generate consolidated monthly and yearly bills for all active students';

    public function handle()
    {
        Log::channel('cron')->info('Starting GenerateBillsCommand (Consolidated)...');
        $this->info('Starting Consolidated Bill Generation...');

        $today = Carbon::today();
        $currentMonthYear = $today->format('Y-m'); 
        $currentYear = $today->year; 
        $currentMonth = $today->month;
        $yearlyBillingMonth = 7; // Juli

        $this->info("Billing period: {$currentMonthYear} (Yearly month: {$yearlyBillingMonth})");

        // 1. Ambil semua siswa aktif
        $students = Student::with(['schoolClass', 'optionalFees']) 
                        ->where('status', 'active')
                        ->get();
        
        if ($students->isEmpty()) {
            Log::channel('cron')->warning('No active students found. Exiting.');
            $this->warn('No active students found.');
            return 1;
        }

        $this->info("Found {$students->count()} active students to process.");
        $totalBillsCreated = 0;

        // 2. Loop setiap siswa
        foreach ($students as $student) {
            
            // ===================================
            // LOGGING DETAIL PER SISWA
            // ===================================
            $this->info("--- Processing Student: {$student->name} (ID: {$student->id}) ---");

            if (!$student->schoolClass) {
                $this->warn("   [SKIP] Student ID: {$student->id} (Missing SchoolClass).");
                Log::channel('cron')->warning("Skipping Student ID: {$student->id} (Missing SchoolClass).");
                continue;
            }

            $gradeLevel = $student->schoolClass->grade_level;
            $schoolId = $student->school_id;
            $this->info(" -> Checking Data: School ID [{$schoolId}], Grade Level [{$gradeLevel}]");
            
            $billingPeriod = $currentMonthYear;
            if ($currentMonth === $yearlyBillingMonth) {
                $billingPeriod .= "_Y" . $currentYear;
            }

            // 3. Cek Duplikasi
            $existingBill = Bill::where('student_id', $student->id)
                                ->where('month', $billingPeriod) 
                                ->exists();

            if ($existingBill) {
                $this->warn("   [SKIP] Bill already exists for this period ({$billingPeriod}).");
                Log::channel('cron')->info("Bill already exists for Student ID {$student->id} for period {$billingPeriod}. Skipping.");
                continue;
            }

            // 4. Ambil semua ATURAN BIAYA yang cocok (SYARAT 1, 2, 3)
            $structuresForStudent = FeeStructure::with('feeCategory')
                                    ->where('school_id', $schoolId) // Syarat 1
                                    ->where('grade_level', $gradeLevel) // Syarat 2
                                    ->where('is_active', true) // Syarat 3
                                    ->get();
            
            if ($structuresForStudent->isEmpty()) {
                $this->warn("   [SKIP] No matching FeeStructures found for School [{$schoolId}] + Grade [{$gradeLevel}].");
                continue;
            }
            $this->info(" -> Found {$structuresForStudent->count()} potentially matching FeeStructures.");

            $itemsToBill = []; 
            $totalAmount = 0;
            $studentOptionalFeeIds = $student->optionalFees->pluck('id');

            // 5. Loop semua aturan & filter
            foreach ($structuresForStudent as $structure) {
                $this->info("   -> Checking Structure: '{$structure->name}' (ID: {$structure->id})");

                if (!$structure->feeCategory) {
                    $this->warn("      [SKIP] FeeStructure ID {$structure->id} missing FeeCategory.");
                    Log::channel('cron')->error("FeeStructure ID {$structure->id} missing FeeCategory. Skipping item.");
                    continue;
                }

                // Cek siklus tagihan (SYARAT 4)
                $isMonthly = $structure->billing_cycle === 'monthly';
                $isYearly = ($structure->billing_cycle === 'yearly' && $currentMonth === $yearlyBillingMonth);
                
                if (!$isMonthly && !$isYearly) {
                    $this->warn("      [SKIP] Billing cycle '{$structure->billing_cycle}' is not active this month.");
                    continue; // Skip 'one_time' atau siklus tidak cocok
                }

                // Cek Wajib vs Opsional (SYARAT 5)
                $shouldBeBilled = false;

                if ($structure->feeCategory->is_optional) {
                    // Jika OPSIONAL
                    if ($studentOptionalFeeIds->contains($structure->id)) {
                        $this->info("      [OK] Student IS SUBSCRIBED to this optional fee.");
                        $shouldBeBilled = true;
                    } else {
                        $this->warn("      [SKIP] Student is NOT SUBSCRIBED to this optional fee.");
                        Log::channel('cron')->info("Student ID {$student->id} skipped optional fee '{$structure->name}' (not subscribed).");
                    }
                } else {
                    // Jika WAJIB
                    $this->info("      [OK] This is a WAJIB fee.");
                    $shouldBeBilled = true;
                }
                
                if ($shouldBeBilled) {
                    $this->info("      [ADD TO CART] Adding '{$structure->name}' (Rp {$structure->amount})");
                    $itemsToBill[] = $structure; 
                    $totalAmount += $structure->amount;
                }
            }

            // 6. Jika keranjang kosong, skip
            if (empty($itemsToBill) || $totalAmount <= 0) {
                $this->warn("   [FINAL SKIP] No items in cart for this student.");
                Log::channel('cron')->info("No items to bill for Student ID {$student->id}. Skipping.");
                continue;
            }

            // 7. Buat Tagihan
            $this->info("   >>> CREATING BILL with Total Amount: {$totalAmount}");
            try {
                DB::transaction(function () use ($student, $itemsToBill, $totalAmount, $billingPeriod, $today) {
                    
                    $parentBill = Bill::create([
                        'foundation_id' => $student->foundation_id,
                        'school_id'     => $student->school_id,
                        'student_id'    => $student->id,
                        'total_amount'  => $totalAmount, 
                        'due_date'      => $today->copy()->addDays(10)->toDateString(),
                        'month'         => $billingPeriod, 
                        'status'        => 'unpaid',
                        'description'   => "Tagihan Gabungan - " . $today->format('F Y') . "a/n" . $student->full_name,

                    ]);

                    $itemsData = [];
                    foreach ($itemsToBill as $itemStructure) {
                        $itemsData[] = [
                            'bill_id'            => $parentBill->id,
                            'fee_structure_id'   => $itemStructure->id,
                            'fee_category_id'  => $itemStructure->fee_category_id,
                            'description'        => $itemStructure->name, 
                            'amount'             => $itemStructure->amount,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ];
                    }
                    
                    BillItem::insert($itemsData); 
                });
                
                Log::channel('cron')->info("SUCCESS: Created consolidated bill for Student ID {$student->id} with {$totalAmount}");
                $this->info("   >>> SUCCESS: Created bill for: {$student->name} (Total: {$totalAmount})");
                $totalBillsCreated++;

            } catch (\Exception $e) {
                Log::channel('cron')->error("FAILED to create bill for Student ID {$student->id}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("   >>> FAILED: {$e->getMessage()}");
            }
        } // Akhir loop siswa

        Log::channel('cron')->info("GenerateBillsCommand finished. Created {$totalBillsCreated} consolidated bills.");
        $this->info("Bill generation finished. Created {$totalBillsCreated} bills.");
        return 0; // Sukses
    }
}