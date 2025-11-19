<?php

namespace App\Filament\Yayasan\Widgets;

use App\Filament\Traits\HasModuleAccess;
use App\Models\JournalEntry;
use App\Models\Foundation;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PemasukanPengeluaranChart extends ChartWidget
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canView(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected ?string $heading = 'Grafik Pemasukan vs Pengeluaran (6 Bulan Terakhir)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = Auth::user();
        $isYayasanUser = $user->school_id === null;
        $schoolId = $user->school_id;

        $dataPemasukan = [];
        $dataPengeluaran = [];
        $labels = [];

        // Tentukan rentang 6 bulan (termasuk bulan ini)
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // 1. Query Pemasukan (Akun 'pendapatan')
        $pemasukan = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'pendapatan') // Ambil dari COA 'pendapatan'
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$startDate, $endDate])
            // Filter per sekolah jika login sebagai Admin Sekolah
            ->when(!$isYayasanUser, fn (Builder $q) => $q->where('journals.school_id', $schoolId))
            ->select(
                DB::raw('SUM(journal_entries.amount) as total'),
                DB::raw("DATE_FORMAT(journals.date, '%Y-%m') as month")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('total', 'month');

        // 2. Query Pengeluaran (Akun 'beban')
        $pengeluaran = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'beban') // Ambil dari COA 'beban'
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$startDate, $endDate])
            // Filter per sekolah jika login sebagai Admin Sekolah
            ->when(!$isYayasanUser, fn (Builder $q) => $q->where('journals.school_id', $schoolId))
            ->select(
                DB::raw('SUM(journal_entries.amount) as total'),
                DB::raw("DATE_FORMAT(journals.date, '%Y-%m') as month")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('total', 'month');
        
        // 3. Format data untuk Chart.js (Looping per 6 bulan)
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $labels[] = Carbon::now()->subMonths($i)->format('M Y'); // Label: "Okt 2025"

            $dataPemasukan[] = $pemasukan->get($month, 0); // Ambil data, jika tidak ada = 0
            $dataPengeluaran[] = $pengeluaran->get($month, 0); // Ambil data, jika tidak ada = 0
        }

        // 4. Kembalikan data dalam format yang dimengerti Chart.js
        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $dataPemasukan,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    'borderColor' => 'rgb(54, 162, 235)',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $dataPengeluaran,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'borderColor' => 'rgb(255, 99, 132)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // 'line' = grafik garis, 'bar' = grafik batang
        return 'line';
    }
}