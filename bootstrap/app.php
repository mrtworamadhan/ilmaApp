<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Jalankan 'app:generate-bills' setiap bulan pada tanggal 1 jam 1:00 pagi
        $schedule->command('app:generate-bills')
                 ->monthlyOn(1, '01:00')
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::channel('cron')->info('Scheduled task app:generate-bills SUCCEEDED.');
                 })
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::channel('cron')->error('Scheduled task app:generate-bills FAILED.');
                 });

        // Jadwal lain bisa ditambahkan di sini
    })
    ->create();
