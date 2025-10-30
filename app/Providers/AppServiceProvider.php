<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\Payment; // <-- 1. Import Payment
use App\Models\Student;
use App\Observers\ExpenseObserver;
use App\Observers\PaymentObserver;
use App\Observers\StudentObserver;
use Illuminate\Support\ServiceProvider;
use Xendit\Configuration;
use Xendit\PaymentMethod\VirtualAccount;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VirtualAccount::class, function ($app) {
            // 1. Buat object konfigurasi
            $config = Configuration::getDefaultConfiguration();
            
            // 2. Set API Key (non-static)
            $config->setApiKey(config('services.xendit.secret_key'));
            
            // 3. Buat dan kembalikan object API dengan konfigurasi tersebut
            return new VirtualAccount($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Payment::observe(PaymentObserver::class);
        Expense::observe(ExpenseObserver::class);
        Student::observe(StudentObserver::class);

    }
}
