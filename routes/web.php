<?php

use App\Livewire\Attendance\RfidKioskTeacher;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\XenditWebhookController;
use App\Livewire\Public\PpdbForm;
use App\Livewire\Kantin\PosUi;
use App\Livewire\Attendance\Kiosk;
use App\Livewire\Attendance\RfidKioskStudent;
use App\Http\Controllers\PayrollController;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/webhooks/xendit', [XenditWebhookController::class, 'handle'])
    ->name('webhooks.xendit');

Route::get('/ppdb', PpdbForm::class)->name('ppdb.form');
Route::get('/absen/kiosk/{school:uuid}', Kiosk::class)
    ->name('kiosk.index');

Route::get('/kantin/pos', function () {
    return view('pos.index');
})->middleware(['auth', 'kantin.access'])->name('pos.index');

Route::get('/absen/rfid/siswa/{school:uuid}', RfidKioskStudent::class) // <-- TAMBAHKAN INI
    ->name('kiosk.rfid.student');

Route::get('/absen/rfid/guru/{school:uuid}', RfidKioskTeacher::class) // <-- TAMBAHKAN INI
    ->name('kiosk.rfid.teacher');

Route::get('/payslips/{payslip}/download-pdf', [PayrollController::class, 'downloadPayslip'])
    ->name('payslip.pdf')
    ->middleware('auth');