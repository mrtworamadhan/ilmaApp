<?php

namespace App\Filament\Yayasan\Widgets;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Foundation;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <-- 1. TAMBAHKAN IMPORT INI
use Illuminate\Support\Number;

class DashboardStatsOverview extends BaseWidget
{
    // public static function canView(): bool
    // {
    //     $tenant = Filament::getTenant();
    //     // Cek apakah tenant ada DAN modul 'finance' aktif
    //     return $tenant instanceof Foundation && $tenant->hasModule('finance');
    // }
    protected static ?int $sort = 1;
    public function getColumns(): int | array
    {
        return 3;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $isYayasanUser = $user->school_id === null;

        // --- 1. Query Data Umum ---
        $siswaQuery = Student::where('status', 'active');
        $tunggakanQuery = Bill::where('status', 'unpaid');
        $pemasukanQuery = Payment::where('status', 'success')
                            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()]);
        
        if (!$isYayasanUser) {
            $siswaQuery->where('school_id', $user->school_id);
            $tunggakanQuery->where('school_id', $user->school_id);
            $pemasukanQuery->where('school_id', $user->school_id);
        }

        $totalSiswa = $siswaQuery->count();
        $totalTunggakan = $tunggakanQuery->sum('amount');
        $pemasukanBulanIni = $pemasukanQuery->sum('amount_paid');

        // --- 2. Buat Kartu-kartu COMMON (untuk semua admin) ---
        $stats = [
            // Stat::make('Total Siswa Aktif', Number::format($totalSiswa))
            //     ->description('Siswa di sekolah Anda')
            //     ->descriptionIcon('heroicon-m-users')
            //     ->color('success'),

            Stat::make('Total Tunggakan', Number::currency($totalTunggakan, 'IDR'))
                ->description('Total tagihan "unpaid"')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Pemasukan Bulan Ini', Number::currency($pemasukanBulanIni, 'IDR'))
                ->description('Total pembayaran "success" bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];

        // --- 3. Buat Kartu-kartu KHUSUS Admin Yayasan ---
        if ($isYayasanUser) {
            
            // a. Hitung Pengeluaran Yayasan
            $pengeluaranBulanIni = Expense::whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
                                        ->sum('amount');
            
            // b. Hitung Siswa per Level
            $siswaPerLevel = Student::query()
                ->join('schools', 'students.school_id', '=', 'schools.id')
                ->where('students.status', 'active')
                // (Otomatis terfilter by foundation_id/tenant)
                ->select('schools.level', DB::raw('COUNT(students.id) as total'))
                ->groupBy('schools.level')
                ->pluck('total', 'level'); // Hasilnya: ['tk' => 5, 'sd' => 10, 'pondok' => 15]

            // c. Buat kartu dinamis dari hasil query
            $levelStats = [];
            // Siapkan kosmetik (sesuai data seeder kita)
            $levelColors = ['tk' => 'info', 'sd' => 'primary', 'pondok' => 'success'];
            $levelIcons = ['tk' => 'heroicon-m-sparkles', 'sd' => 'heroicon-m-academic-cap', 'pondok' => 'heroicon-m-building-library'];

            foreach ($siswaPerLevel as $level => $total) {
                $levelStats[] = Stat::make('Siswa ' . strtoupper($level), Number::format($total))
                                    ->description('Siswa aktif di tingkat ' . $level)
                                    ->descriptionIcon($levelIcons[$level] ?? 'heroicon-m-users')
                                    ->color($levelColors[$level] ?? 'gray');
            }

            // d. Tambahkan kartu Pengeluaran
            $levelStats[] = Stat::make('Pengeluaran Bulan Ini', Number::currency($pengeluaranBulanIni, 'IDR'))
                                ->description('Total pengeluaran yayasan')
                                ->descriptionIcon('heroicon-m-arrow-trending-down')
                                ->color('warning');
            
            // e. Gabungkan semua kartu Yayasan ke kartu common
            // (array_merge agar kartu level muncul duluan)
            $stats = array_merge($levelStats, $stats);
        }

        return $stats;
    }
}