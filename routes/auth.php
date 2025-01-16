<?php

use App\Http\Controllers\Api\v1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\v1\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\v1\Auth\NewPasswordController;
use App\Http\Controllers\Api\v1\Auth\OtpController;
use App\Http\Controllers\Api\v1\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\v1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\v1\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;


Route::prefix('register')
    ->middleware('guest')
    ->group(function () {
        Route::post('/', [RegisteredUserController::class, 'store'])->name('register');
        Route::post('step-1', [RegisteredUserController::class, 'step1'])->name('step-1');
        Route::post('step-2', [RegisteredUserController::class, 'step2'])->name('step-2');
    })->name('register');

Route::post('/invite-register', [RegisteredUserController::class, 'invitation']);

Route::prefix('otp')->controller(OtpController::class)->group(function () {
    Route::post('verify', 'verify');
    Route::post('resend', 'resend');
});


Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
