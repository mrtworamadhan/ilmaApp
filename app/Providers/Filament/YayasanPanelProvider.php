<?php

namespace App\Providers\Filament;

// Model & Fasade
use App\Filament\Yayasan\Resources\AdmissionBatches\AdmissionBatchResource;
use App\Filament\Yayasan\Resources\AdmissionBatches\Tables\AdmissionBatchesTable;
use App\Filament\Yayasan\Resources\AdmissionRegistrations\AdmissionRegistrationResource;
use App\Filament\Yayasan\Resources\Announcements\AnnouncementResource;
use App\Filament\Yayasan\Resources\Payroll\Payslips\PayslipResource;
use App\Filament\Yayasan\Resources\PayrollComponents\PayrollComponentResource;
use App\Filament\Yayasan\Resources\StudentAttendances\StudentAttendanceResource;
use App\Filament\Yayasan\Resources\StudentRecords\StudentRecordResource;
use App\Filament\Yayasan\Resources\TeacherAttendances\TeacherAttendanceResource;
use App\Filament\Yayasan\Resources\Teachers\TeacherResource;
use App\Filament\Yayasan\Resources\VendorDisbursements\VendorDisbursementResource;
use App\Filament\Yayasan\Resources\Vendors\VendorResource;
use App\Models\Foundation;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;

// Middleware
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Navigasi & Halaman
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Support\Colors\Color;
use Filament\Widgets;

// Halaman Default
use App\Filament\Yayasan\Resources\Departments\DepartmentResource;
use App\Filament\Yayasan\Resources\Roles\RoleResource;
use App\Filament\Yayasan\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Yayasan\Resources\Schools\SchoolResource;
use App\Filament\Yayasan\Resources\Students\StudentResource;
use App\Filament\Yayasan\Resources\Users\UserResource;

// Halaman Keuangan & Akuntansi
use App\Filament\Yayasan\Resources\Accounts\AccountResource;
use App\Filament\Yayasan\Resources\Bills\BillResource;
use App\Filament\Yayasan\Resources\Expenses\ExpenseResource;
use App\Filament\Yayasan\Resources\FeeCategories\FeeCategoryResource;
use App\Filament\Yayasan\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Yayasan\Resources\Journals\JournalResource;
use App\Filament\Yayasan\Resources\Payments\PaymentResource;

// Halaman Anggaran
use App\Filament\Yayasan\Resources\Budgets\BudgetResource;
use App\Filament\Yayasan\Resources\DisbursementRequests\DisbursementRequestResource;

// Halaman Tabungan
use App\Filament\Yayasan\Resources\SavingAccounts\SavingAccountResource;
use App\Filament\Yayasan\Resources\SavingTransactions\SavingTransactionResource;

// Halaman Laporan
use App\Filament\Yayasan\Pages\LaporanBukuBesar;
use App\Filament\Yayasan\Pages\LaporanLabaRugi;
use App\Filament\Yayasan\Pages\LaporanNeraca;
use App\Filament\Yayasan\Pages\LaporanRealisasiAnggaran;
use App\Filament\Yayasan\Pages\LaporanTunggakan;

// Widget
use App\Filament\Yayasan\Widgets\DashboardFinancialOverview;
use App\Filament\Yayasan\Widgets\DashboardStatsOverview;
use App\Filament\Yayasan\Widgets\PemasukanPengeluaranChart;


class YayasanPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('yayasan')
            ->path('yayasan')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                'role:Admin Yayasan|Admin Sekolah|Staf|Guru|Staf Kesiswaan|Wali Kelas|Kepala Bagian', // Pastikan HANYA role ini yg boleh masuk
            ])
            
            ->tenant(
                model: Foundation::class,
                slugAttribute: 'name',
                ownershipRelationship: 'foundation'
            )
            // ->discoverResources(in: app_path('Filament/Yayasan/Resources'), for: 'App\Filament\Yayasan\Resources')
            ->discoverPages(in: app_path('Filament/Yayasan/Pages'), for: 'App\Filament\Yayasan\Pages')
            // ->discoverWidgets(in: app_path('Filament/Yayasan/Widgets'), for: 'App\Filament\Yayasan\Widgets')
            
            // ->navigationGroups([
            //     NavigationGroup::make('Pengaturan')
            //         ->label('Pengaturan'),
            //     NavigationGroup::make('Data Master')
            //         ->label('Data Master'),
            //     NavigationGroup::make('Kesiswaan')
            //         ->label('Kesiswaan'), 
            //     NavigationGroup::make('Kepegawaian')
            //         ->label('Kepegawaian'),
            //     NavigationGroup::make('Manajemen Keuangan')
            //         ->label('Keuangan'), 
            //     NavigationGroup::make('Tabungan')
            //         ->label('Tabungan'), 
            //     NavigationGroup::make('Akuntansi')
            //         ->label('Akuntansi'), 
            //     NavigationGroup::make('Anggaran')
            //         ->label('Anggaran'), 
            //     NavigationGroup::make('Laporan')
            //         ->label('Laporan'),
            //     NavigationGroup::make('Laporan')
            //         ->label('Laporan'), 
            // ])

            ->resources([
                // Default
                SchoolResource::class,
                UserResource::class,
                DepartmentResource::class,
                SchoolClassResource::class,
                StudentResource::class,
                // RoleResource::class,

                // Keuangan
                AccountResource::class,
                BillResource::class,
                PaymentResource::class,
                FeeCategoryResource::class,
                FeeStructureResource::class,
                JournalResource::class,
                ExpenseResource::class,
                
                // Anggaran
                BudgetResource::class,
                DisbursementRequestResource::class,
                
                // Tabungan (terpisah)
                SavingAccountResource::class,
                SavingTransactionResource::class,

                //kepegawaian
                TeacherResource::class,
                TeacherAttendanceResource::class,
                //Payroll
                PayrollComponentResource::class,
                PayslipResource::class,

                //ppdb
                AdmissionRegistrationResource::class,
                AdmissionBatchResource::class,

                //Broadcast
                AnnouncementResource::class,

                //Kesiswaan
                StudentAttendanceResource::class,
                StudentRecordResource::class,

                //VendorKantin
                VendorDisbursementResource::class,
                VendorResource::class,

            ])
            
            ->pages([
                Dashboard::class, 
                LaporanBukuBesar::class,
                LaporanLabaRugi::class,
                LaporanNeraca::class,
                LaporanRealisasiAnggaran::class,
                LaporanTunggakan::class,
            ])
            
            ->widgets([
                DashboardStatsOverview::class,
                Widgets\AccountWidget::class,

                DashboardFinancialOverview::class,
                PemasukanPengeluaranChart::class,
            ])
            // ->active(Dashboard::class)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}