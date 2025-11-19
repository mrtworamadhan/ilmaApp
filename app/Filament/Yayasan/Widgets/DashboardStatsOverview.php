<?php

namespace App\Filament\Yayasan\Widgets;

use App\Filament\Traits\HasModuleAccess;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\Foundation;
use App\Models\Teacher;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <-- 1. TAMBAHKAN IMPORT INI
use Illuminate\Support\Number;

class DashboardStatsOverview extends BaseWidget
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canView(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static ?int $sort = 1;
    public function getColumns(): int | array
    {
        return 3;
    }
    protected static bool $isLazy = false; 

    protected function getStats(): array
    {
        $foundationId = Filament::getTenant()->id;
        $user = auth()->user();
        
        $stats = [];

        if ($user->hasRole('Admin Yayasan')) {
            // ===================================
            // LOGIKA ADMIN YAYASAN (PER TINGKAT)
            // ===================================
            $schools = School::where('foundation_id', $foundationId)->get();
            
            // Stat Siswa per Sekolah (Logika kamu, sudah benar)
            foreach ($schools as $school) {
                $stats[] = Stat::make(
                    "Siswa {$school->name}", // misal: "Siswa TK ILMA"
                    Student::where('school_id', $school->id)->where('status', 'active')->count()
                )->icon('heroicon-o-users');
            }
            
            // Stat Guru per Sekolah (Logika kamu, sudah benar)
            foreach ($schools as $school) {
                $stats[] = Stat::make(
                    "Guru {$school->name}", // misal: "Guru TK ILMA"
                    Teacher::where('school_id', $school->id)->count()
                )->icon('heroicon-o-academic-cap');
            }
            
            // Stat Tunggakan (Kita tambahkan di level Yayasan)
            $stats[] = Stat::make('Total Tunggakan (Yayasan)',
                    Number::currency(
                        Bill::query()
                            ->where('foundation_id', $foundationId)
                            ->whereIn('status', ['unpaid', 'overdue'])
                            ->sum('total_amount'), // <-- 3. PERBAIKAN
                        'IDR'
                    )
                )
                ->description('Total tagihan belum lunas di semua sekolah')
                ->color('danger')
                ->icon('heroicon-o-currency-dollar');

        } elseif ($user->school_id) {
            // ===================================
            // LOGIKA ADMIN SEKOLAH (TOTAL)
            // ===================================
            $schoolId = $user->school_id;
            
            $stats[] = Stat::make('Total Siswa Aktif (Sekolah Ini)',
                Student::where('school_id', $schoolId)->where('status', 'active')->count()
            )->icon('heroicon-o-users');
            
            $stats[] = Stat::make('Total Guru Aktif (Sekolah Ini)',
                Teacher::where('school_id', $schoolId)->where('status', 'active')->count()
            )->icon('heroicon-o-academic-cap');

            // Stat Tunggakan (Kita tambahkan di level Sekolah)
            $stats[] = Stat::make('Total Tunggakan (Sekolah Ini)',
                    Number::currency(
                        Bill::query()
                            ->where('school_id', $schoolId) // <-- Filter per sekolah
                            ->whereIn('status', ['unpaid', 'overdue'])
                            ->sum('total_amount'), // <-- 3. PERBAIKAN
                        'IDR'
                    )
                )
                ->description('Total tagihan belum lunas di sekolah ini')
                ->color('danger')
                ->icon('heroicon-o-currency-dollar');
        }
        
        return $stats;
    }
}