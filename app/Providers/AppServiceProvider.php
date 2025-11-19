<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\Foundation;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Budget;
use App\Models\DisbursementRequest;
use App\Observers\FoundationObserver;
use App\Policies\BudgetPolicy;
use App\Policies\DisbursementRequestPolicy;
use App\Observers\DisbursementRequestObserver;
use App\Observers\ExpenseObserver;
use App\Observers\PaymentObserver;
use App\Observers\StudentObserver;
use App\Models\SavingTransaction;
use App\Observers\SavingTransactionObserver;
use Illuminate\Support\ServiceProvider;
use Xendit\Configuration;
use Xendit\PaymentMethod\VirtualAccount;
use Illuminate\Support\Facades\Gate;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;

class AppServiceProvider extends ServiceProvider
{
    /**
     * HAPUS PROPERTI $policies DARI SINI
     */

    /**
     * Register any application services.
     */
    public function register(): void
    {
        
        $this->app->singleton(VirtualAccount::class, function ($app) {
            $config = Configuration::getDefaultConfiguration();    
            $config->setApiKey(config('services.xendit.secret_key'));
            return new VirtualAccount($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentAsset::register([
            // Css::make('custom-stylesheet', __DIR__ . '/../../resources/css/custom.css'),
            // Js::make('custom-script', __DIR__ . '/../../resources/js/custom.js'),
        ]);
        // 2. DAFTARKAN POLICIES SECARA MANUAL DI SINI
        Gate::policy(Budget::class, BudgetPolicy::class);
        Gate::policy(DisbursementRequest::class, DisbursementRequestPolicy::class);

        // 3. OBSERVER ANDA SUDAH BENAR DI SINI
        Foundation::observe(FoundationObserver::class);
        Payment::observe(PaymentObserver::class);
        Expense::observe(ExpenseObserver::class);
        Student::observe(StudentObserver::class);
        DisbursementRequest::observe(DisbursementRequestObserver::class);
        SavingTransaction::observe(SavingTransactionObserver::class);
    }
}