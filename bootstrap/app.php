<?php

use App\Http\Middleware\CheckSchoolApiKey;
use App\Http\Middleware\CheckKantinPanelAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            // 'yayasan.access' => ::class,
            
            'kantin.access' => CheckKantinPanelAccess::class,
            'school.api' => CheckSchoolApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('app:generate-bills')
                 ->monthlyOn(1, '01:00')
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::channel('cron')->info('Scheduled task app:generate-bills SUCCEEDED.');
                 })
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::channel('cron')->error('Scheduled task app:generate-bills FAILED.');
                 });

    })
    ->create();
