<?php

use App\Http\Controllers\Api\v1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\v1\Auth\NewPasswordController;
use App\Http\Controllers\Api\v1\Auth\OtpController;
use App\Http\Controllers\Api\v1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\v1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\v1\ProfileController;
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

Route::post('forgot-password', [ResetPasswordController::class, 'forgot'])
    ->middleware('guest');

Route::post('new-password', [NewPasswordController::class, 'store']);

Route::post('reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware('auth:sanctum');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');

Route::post('user', [ProfileController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('user.destroy');
