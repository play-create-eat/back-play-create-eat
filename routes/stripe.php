<?php

use App\Http\Controllers\Api\v1\StripePaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('stripe')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('wallet/checkout', [StripePaymentController::class, 'createCheckoutSession'])->name('stripe.checkout');
        Route::get('wallet/{family}/success', [StripePaymentController::class, 'successPayment'])->name('stripe.success');
        Route::get('wallet/cancel', [StripePaymentController::class, 'cancelPayment'])->name('stripe.cancel');
        Route::get('wallet/transactions', [StripePaymentController::class, 'transactions'])->name('stripe.transactions');
        Route::get('wallet/balance', [StripePaymentController::class, 'balance'])->name('stripe.balance');
        Route::post('webhook', [StripePaymentController::class, 'handleWebhook'])->name('stripe.webhook');
    });
