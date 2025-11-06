<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\XenditWebhookController;
use App\Livewire\Public\PpdbForm;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/webhooks/xendit', [XenditWebhookController::class, 'handle'])
    ->name('webhooks.xendit');

Route::get('/ppdb', PpdbForm::class)->name('ppdb.form');
