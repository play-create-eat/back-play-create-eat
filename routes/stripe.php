<?php

use App\Http\Controllers\Api\v1\StripePaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('stripe')->group(function () {
    Route::post('wallet/{family}/checkout', [StripePaymentController::class, 'createCheckoutSession'])->name('stripe.checkout');
    Route::get('wallet/{family}/success', [StripePaymentController::class, 'successPayment'])->name('stripe.success');
    Route::get('wallet/{family}/cancel', [StripePaymentController::class, 'cancelPayment'])->name('stripe.cancel');
});
