<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateBillsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Nama perintah kita: php artisan app:generate-bills
    protected $signature = 'app:generate-bills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly and yearly bills automatically based on fee structures';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::channel('cron')->info('Starting GenerateBillsCommand...');
        $this->info('Starting bill generation...');

        $today = Carbon::today();
        $currentMonthYear = $today->format('Y-m'); // Format: 2025-10
        $currentYear = $today->year;
        $currentMonth = $today->month; // Angka bulan (1-12)

        // Tentukan bulan untuk tagihan tahunan (misal: Juli)
        $yearlyBillingMonth = 7; // Juli

        // --- 1. Ambil Semua Aturan Biaya Aktif ---
        $feeStructures = FeeStructure::where('is_active', true)
                            ->with(['school', 'feeCategory']) // Eager load relasi
                            ->get();

        if ($feeStructures->isEmpty()) {
            Log::channel('cron')->warning('No active fee structures found. Exiting.');
            $this->warn('No active fee structures found.');
            return 1; // Keluar jika tidak ada aturan
        }

        // --- 2. Loop Setiap Aturan Biaya ---
        foreach ($feeStructures as $structure) {
            $this->info("Processing structure: {$structure->name} (School: {$structure->school->name}, Category: {$structure->feeCategory->name})");

            // Tentukan apakah aturan ini relevan untuk dijalankan sekarang
            $shouldGenerate = false;
            $billingMonthYear = null; // Bulan spesifik untuk tagihan

            if ($structure->billing_cycle === 'monthly') {
                $shouldGenerate = true;
                $billingMonthYear = $currentMonthYear;
                Log::channel('cron')->info("Monthly structure identified for {$billingMonthYear}.");
            } elseif ($structure->billing_cycle === 'yearly' && $currentMonth === $yearlyBillingMonth) {
                $shouldGenerate = true;
                $billingMonthYear = $currentYear . '-YEARLY'; // Penanda tagihan tahunan
                Log::channel('cron')->info("Yearly structure identified for {$billingMonthYear}.");
            } elseif ($structure->billing_cycle === 'one_time') {
                // one_time tidak digenerate otomatis di sini, tapi via input manual atau event lain (PPDB)
                Log::channel('cron')->info("One-time structure skipped.");
                continue;
            }

            if (!$shouldGenerate) {
                Log::channel('cron')->info("Structure skipped (not relevant for today).");
                continue; // Lanjut ke aturan berikutnya jika tidak relevan
            }

            // --- 3. Cari Siswa yang Sesuai ---
            $studentsQuery = Student::where('status', 'active') // Hanya siswa aktif
                                ->where('school_id', $structure->school_id)
                                ->with('schoolClass'); // Eager load kelas untuk cek grade_level

            // Filter berdasarkan grade_level jika ada di aturan
            if ($structure->grade_level) {
                $studentsQuery->whereHas('schoolClass', function ($query) use ($structure) {
                    $query->where('grade_level', $structure->grade_level);
                });
                Log::channel('cron')->info("Filtering students by grade_level: {$structure->grade_level}");
            }

            $students = $studentsQuery->get();

            if ($students->isEmpty()) {
                Log::channel('cron')->warning("No active students found matching the criteria for structure ID: {$structure->id}");
                continue; // Lanjut jika tidak ada siswa
            }

             $this->info("Found {$students->count()} students for this structure.");

            // --- 4. Loop Setiap Siswa & Buat Tagihan ---
            $billsCreatedCount = 0;
            foreach ($students as $student) {
                // Cek apakah siswa ini terdaftar di biaya opsional INI
                $isOptionalSubscribed = false;
                if ($structure->billing_cycle !== 'one_time') { // Asumsi biaya opsional itu rutin
                    // Cek tabel pivot student_optional_fees
                    $isOptionalSubscribed = DB::table('student_optional_fees')
                                                ->where('student_id', $student->id)
                                                ->where('fee_structure_id', $structure->id)
                                                ->exists();

                    // Jika aturan INI adalah biaya opsional tapi siswa TIDAK subscribe, skip
                    // Logika ini mengasumsikan SEMUA aturan bisa jadi opsional,
                    // mungkin perlu penanda 'is_optional' di fee_structures
                    // Untuk sementara, kita anggap hanya yang ada di pivot yg opsional & wajib ada
                    // Atau: Jika Kategori BUKAN SPP/Uang Gedung, ANGGAP opsional? -> perlu diskusi
                    $isPotentiallyOptional = !in_array($structure->feeCategory->name, ['SPP Bulanan', 'Uang Gedung']); // Contoh asumsi
                    if ($isPotentiallyOptional && !$isOptionalSubscribed) {
                         Log::channel('cron')->info("Student ID {$student->id} skipped optional fee '{$structure->name}' (not subscribed).");
                         continue;
                    }
                     if ($isOptionalSubscribed) {
                         Log::channel('cron')->info("Student ID {$student->id} IS subscribed to optional fee '{$structure->name}'.");
                     }

                }


                // Cek Duplikasi: Apakah sudah ada tagihan untuk siswa ini, kategori ini, dan bulan/tahun ini?
                $existingBill = Bill::where('student_id', $student->id)
                                    ->where('fee_category_id', $structure->fee_category_id)
                                    ->where('month', $billingMonthYear) // Cocokkan bulan/tahun
                                    ->exists();

                if ($existingBill) {
                     Log::channel('cron')->info("Bill already exists for Student ID {$student->id}, Category ID {$structure->fee_category_id}, Month {$billingMonthYear}. Skipping.");
                     continue; // Jangan buat duplikat
                }

                // Buat Tagihan Baru
                try {
                    Bill::create([
                        'foundation_id' => $student->foundation_id,
                        'school_id' => $student->school_id,
                        'student_id' => $student->id,
                        'fee_category_id' => $structure->fee_category_id,
                        'amount' => $structure->amount,
                        'due_date' => $today->copy()->addDays(10)->toDateString(), // Jatuh tempo 10 hari
                        'month' => $billingMonthYear, // Simpan periode tagihan
                        'status' => 'unpaid',
                        'description' => $structure->name . ($billingMonthYear ? " - Periode " . $billingMonthYear : ''),
                    ]);
                    $billsCreatedCount++;
                    Log::channel('cron')->info("Bill created for Student ID {$student->id}, Structure ID {$structure->id}.");

                } catch (\Exception $e) {
                     Log::channel('cron')->error("Failed to create bill for Student ID {$student->id}, Structure ID {$structure->id}", [
                        'error' => $e->getMessage()
                     ]);
                }
            }
             $this->info("Created {$billsCreatedCount} bills for structure: {$structure->name}");
        }

        Log::channel('cron')->info('GenerateBillsCommand finished.');
        $this->info('Bill generation finished.');
        return 0; // Sukses
    }
}