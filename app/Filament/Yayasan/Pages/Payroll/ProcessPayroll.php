<?php

namespace App\Filament\Yayasan\Pages\Payroll;

use App\Filament\Traits\HasModuleAccess;
use App\Models\Account; // <-- 1. IMPORT MODEL ACCOUNT
use App\Models\Expense;
use App\Models\Payroll\Payslip;
use App\Models\Teacher;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use BackedEnum;
use UnitEnum;
use Filament\Support\Icons\Heroicon;


class ProcessPayroll extends Page implements HasForms
{
    use InteractsWithForms;
    use HasModuleAccess;
    protected static string $requiredModule = 'payroll';

    public static function canAccess(): bool // <-- BENAR (Ini untuk Page)
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;
    protected static string | UnitEnum | null $navigationGroup  = 'Payroll';
    protected static ?string $navigationLabel = 'Proses Gaji Bulanan';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.yayasan.pages.payroll.process-payroll';

    protected static ?string $title = 'Proses Gaji Bulanan';

    // --- State untuk Form ---
    public ?array $data = [];

    /**
     * Set default bulan & tahun ke bulan ini
     */
    public function mount(): void
    {
        $this->form->fill([
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    /**
     * Definisi Form
     */
    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Select::make('month')
                    ->label('Pilih Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->required(),
                Select::make('year')
                    ->label('Pilih Tahun')
                    ->options(array_combine(range(now()->year, now()->year - 5), range(now()->year, now()->year - 5)))
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    /**
     * Method ini dipanggil saat tombol "Generate" di-klik
     */
    public function submit(): void
    {
        $formData = $this->form->getState();
        $month = $formData['month'];
        $year = $formData['year'];
        $foundationId = Auth::user()->foundation_id;
        $adminUserId = Auth::id(); // Admin yang memproses

        Log::info("--- PROSES PAYROLL DIMULAI: Bulan {$month}/{$year} oleh User ID: {$adminUserId} ---");

        // ========================================================
        // REFACTOR DIMULAI DI SINI
        // ========================================================

        // 1. Ambil Akun Sistem untuk Jurnal
        $akunBebanGaji = Account::where('foundation_id', $foundationId)
                               ->where('system_code', 'beban_gaji_default')
                               ->first();

        $akunKas = Account::where('foundation_id', $foundationId)
                         ->where('system_code', 'kas_operasional_default')
                         ->first();

        // Guard Clause: Hentikan proses jika akun sistem tidak ada
        if (!$akunBebanGaji || !$akunKas) {
            $missing = !$akunBebanGaji ? 'beban_gaji_default' : 'kas_operasional_default';
            Log::error("GAGAL TOTAL: Akun sistem '{$missing}' tidak ditemukan untuk Foundation ID: {$foundationId}. Proses payroll dibatalkan.");
            Notification::make()
                ->title('Proses Gagal: Akun Sistem Tidak Ditemukan')
                ->body("Akun '{$missing}' belum di-setup di COA. Hubungi Super Admin.")
                ->danger()
                ->send();
            return; // Hentikan eksekusi
        }

        // ========================================================
        // REFACTOR SELESAI
        // ========================================================


        // 2. Ambil semua guru di yayasan ini, LENGKAP dengan setting gajinya
        $teachers = Teacher::where('foundation_id', $foundationId)
            ->with('payrolls.payrollComponent') // Eager load setting gajinya
            ->get();

        $processedCount = 0;
        $skippedCount = 0;

        foreach ($teachers as $teacher) {
            // 3. Cek apakah slip gaji sudah ada
            $existing = Payslip::where('teacher_id', $teacher->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($existing) {
                Log::warning("Skipped: Slip gaji untuk {$teacher->full_name} bulan {$month}/{$year} sudah ada.");
                $skippedCount++;
                continue; // Lanjut ke guru berikutnya
            }

            // 4. Hitung Gaji
            $totalAllowance = $teacher->payrolls->where('payrollComponent.type', 'allowance')->sum('amount');
            $totalDeduction = $teacher->payrolls->where('payrollComponent.type', 'deduction')->sum('amount');
            $netPay = $totalAllowance - $totalDeduction;

            // 5. Proses dalam Transaksi Database
            try {
                DB::transaction(function () use ($teacher, $month, $year, $totalAllowance, $totalDeduction, $netPay, $adminUserId, $akunBebanGaji, $akunKas) {
                    
                    // A. Buat Header Slip Gaji (Payslip)
                    $payslip = Payslip::create([
                        'foundation_id' => $teacher->foundation_id,
                        'school_id' => $teacher->school_id,
                        'teacher_id' => $teacher->id,
                        'month' => $month,
                        'year' => $year,
                        'total_allowance' => $totalAllowance,
                        'total_deduction' => $totalDeduction,
                        'net_pay' => $netPay,
                        'status' => 'generated', // Status awal
                    ]);

                    // B. "Foto" Rincian Gaji (PayslipDetail)
                    foreach ($teacher->payrolls as $component) {
                        $payslip->details()->create([
                            'component_name' => $component->payrollComponent->name,
                            'type' => $component->payrollComponent->type,
                            'amount' => $component->amount,
                        ]);
                    }

                    // C. Integrasi: Buat Expense (Ini akan memicu ExpenseObserver)
                    // REFACTOR: Gunakan ID akun dinamis
                    $expense = Expense::create([
                        'foundation_id' => $teacher->foundation_id,
                        'school_id' => $teacher->school_id,
                        'expense_account_id' => $akunBebanGaji->id, // <-- REFACTOR
                        'cash_account_id' => $akunKas->id,       // <-- REFACTOR
                        'amount' => $netPay,
                        'description' => "Pembayaran Gaji: {$teacher->full_name} - {$month}/{$year}",
                        'date' => now(),
                    ]);

                    // D. Tautkan Expense ke Payslip
                    $payslip->update(['expense_id' => $expense->id, 'status' => 'paid']);

                });

                $processedCount++;
                Log::info("Sukses: Slip gaji untuk {$teacher->full_name} dibuat.");

            } catch (\Exception $e) {
                Log::error("GAGAL proses gaji {$teacher->full_name}: " . $e->getMessage());
                // Kirim notifikasi error tapi jangan hentikan loop
                Notification::make()
                    ->title("Gagal Proses Gaji: {$teacher->full_name}")
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        // 6. Kirim Notifikasi Sukses
        Log::info("--- PROSES PAYROLL SELESAI ---");
        Notification::make()
            ->title('Proses Gaji Selesai')
            ->body("Berhasil memproses {$processedCount} slip gaji. {$skippedCount} slip gaji dilewati (sudah ada).")
            ->success()
            ->send();
    }
}