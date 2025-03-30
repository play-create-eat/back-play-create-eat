<?php

use App\Http\Controllers\Api\v1\Internal\AuthenticatedSessionController;
use App\Http\Controllers\Api\v1\Internal\ProductTypeController;
use App\Http\Controllers\Api\v1\Internal\PassController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')
    ->middleware('auth:internal-api')
    ->group(function () {
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->withoutMiddleware('auth:internal-api')
            ->middleware('guest')
            ->name('internal.login');

        Route::get('/product-type', [ProductTypeController::class, 'index'])
            ->name('internal.product-type.list');

        Route::post('/pass/scan', [PassController::class, 'scan'])
            ->name('internal.pass.scan');

        Route::post('/pass/info', [PassController::class, 'info'])
            ->name('internal.pass.info');
    });
