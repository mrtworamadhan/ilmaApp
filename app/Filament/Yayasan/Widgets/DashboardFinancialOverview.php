<?php

namespace App\Filament\Yayasan\Widgets;

use App\Models\Account;
use App\Models\BudgetItem;
use App\Models\Department;
use App\Models\JournalEntry;
use Filament\Facades\Filament;
use App\Models\Foundation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class DashboardFinancialOverview extends BaseWidget
{
    // Atur urutan, letakkan di bawah (setelah charts)
    protected static ?int $sort = 3;

    /**
     * Tampilkan widget ini HANYA untuk Admin Yayasan dan Admin Sekolah.
     */
    public static function canView(): bool
    {
        $tenant = Filament::getTenant();
        return $tenant instanceof Foundation && $tenant->hasModule('finance');
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $tenant = Filament::getTenant();
        $isYayasanUser = $user->school_id === null;
        
        $stats = [];

        // ==========================================================
        // KARTU 1: SALDO TERSEDIA (KAS OPERASIONAL - 1101)
        // ==========================================================
        $akunKasOps = Account::where('foundation_id', $tenant->id)
                            ->where('code', '1101') // Kas Operasional
                            ->first();
        
        $saldoKasOps = $this->getSaldoAkun(
            $akunKasOps, 
            $isYayasanUser, 
            $user->school_id
        );

        $stats[] = Stat::make('Saldo Kas Tersedia', Number::currency($saldoKasOps, 'IDR'))
            ->description('Kas operasional (di luar tabungan)')
            ->descriptionIcon('heroicon-m-wallet')
            ->color('success');

        // ==========================================================
        // KARTU 2: DANA TABUNGAN SISWA (UTANG - 2102)
        // ==========================================================
        $akunUtangTabungan = Account::where('foundation_id', $tenant->id)
                            ->where('code', '2102') // Utang Tabungan Siswa
                            ->first();
                            
        $saldoTabungan = $this->getSaldoAkun(
            $akunUtangTabungan, 
            $isYayasanUser, 
            $user->school_id
        );

        $stats[] = Stat::make('Total Dana Tabungan Siswa', Number::currency($saldoTabungan, 'IDR'))
            ->description('Total uang siswa yang dititipkan')
            ->descriptionIcon('heroicon-m-building-library')
            ->color('info');

        // ==========================================================
        // KARTU 3: REALISASI ANGGARAN (DINAMIS)
        // ==========================================================
        
        // Buat query dasar untuk anggaran & realisasi
        $baseQuery = fn () => BudgetItem::query()
            ->whereHas('budget', fn (Builder $q) => $q->where('status', 'APPROVED'))
            ->where('foundation_id', $tenant->id);

        if ($isYayasanUser) {
            // --- Tampilan Admin Yayasan (per Level) ---
            $levels = ['tk', 'sd', 'pondok'];
            foreach ($levels as $level) {
                // Query Anggaran
                $anggaran = (clone $baseQuery())
                    ->whereHas('budget.department.school', fn(Builder $q) => $q->where('level', $level))
                    ->sum('planned_amount');
                
                // Query Realisasi
                $realisasi = (clone $baseQuery())
                    ->whereHas('budget.department.school', fn(Builder $q) => $q->where('level', $level))
                    ->withSum('expenses', 'amount')
                    ->get()
                    ->sum('expenses_sum_amount');

                $persen = ($anggaran > 0) ? ($realisasi / $anggaran) * 100 : 0;

                $stats[] = Stat::make('Realisasi ' . strtoupper($level), Number::currency($realisasi, 'IDR'))
                    ->description(Number::format($persen, precision: 1) . '% dari Anggaran ' . Number::currency($anggaran, 'IDR'))
                    ->color('warning');
            }

        } else {
            // --- Tampilan Admin Sekolah (per Departemen) ---
            $departments = Department::where('school_id', $user->school_id)->get();
            foreach ($departments as $dept) {
                // Query Anggaran
                $anggaran = (clone $baseQuery())
                    ->whereHas('budget', fn(Builder $q) => $q->where('department_id', $dept->id))
                    ->sum('planned_amount');

                // Query Realisasi
                $realisasi = (clone $baseQuery())
                    ->whereHas('budget', fn(Builder $q) => $q->where('department_id', $dept->id))
                    ->withSum('expenses', 'amount')
                    ->get()
                    ->sum('expenses_sum_amount');
                
                $persen = ($anggaran > 0) ? ($realisasi / $anggaran) * 100 : 0;

                $stats[] = Stat::make('Realisasi ' . $dept->name, Number::currency($realisasi, 'IDR'))
                    ->description(Number::format($persen, precision: 1) . '% dari Anggaran ' . Number::currency($anggaran, 'IDR'))
                    ->color('warning');
            }
        }

        return $stats;
    }

    /**
     * Helper untuk menghitung saldo akun dari Jurnal
     * (Menduplikasi logika LaporanNeraca.php)
     */
    private function getSaldoAkun(?Account $account, bool $isYayasanUser, ?int $schoolId): float
    {
        if (!$account) {
            return 0;
        }

        $tenantId = Filament::getTenant()->id;
        
        $baseQuery = fn () => JournalEntry::query()
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->where('journals.foundation_id', $tenantId)
            ->where('journal_entries.account_id', $account->id)
            ->when(!$isYayasanUser, fn(Builder $q) => $q->where('journals.school_id', $schoolId));

        $debit = (clone $baseQuery())->where('type', 'debit')->sum('amount');
        $kredit = (clone $baseQuery())->where('type', 'kredit')->sum('amount');

        // Saldo normal Akun
        if (in_array($account->type, ['aktiva', 'beban'])) {
            return $debit - $kredit;
        } else {
            return $kredit - $debit;
        }
    }
}