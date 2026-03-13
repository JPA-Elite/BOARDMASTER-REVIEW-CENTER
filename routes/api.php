<?php

use App\Http\Controllers\Api\PaymongoCheckoutController;
use App\Http\Controllers\Webhooks\PaymongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/paymongo', PaymongoWebhookController::class)
    ->name('api.webhooks.paymongo');

Route::post('/paymongo/checkout', [PaymongoCheckoutController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('api.paymongo.checkout');
