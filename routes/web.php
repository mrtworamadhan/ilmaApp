<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\XenditWebhookController;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/webhooks/xendit', [XenditWebhookController::class, 'handle'])
    ->name('webhooks.xendit');
