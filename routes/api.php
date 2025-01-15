<?php

use App\Http\Controllers\Api\v1\ChildController;
use App\Http\Controllers\Api\v1\DocumentController;
use App\Http\Controllers\Api\v1\InvitationController;
use App\Http\Controllers\Api\v1\PandaDocController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('user', function (Request $request) {
            return response()->json($request->user());
        });

        Route::get('children', [ChildController::class, 'index'])
            ->withoutMiddleware('auth:sanctum');
        Route::post('children', [ChildController::class, 'store'])
            ->withoutMiddleware('auth:sanctum');
        Route::delete('children/{child}', [ChildController::class, 'destroy'])->withoutMiddleware('auth:sanctum');

        Route::post('documents', [DocumentController::class, 'store'])
            ->withoutMiddleware('auth:sanctum');
        Route::post('documents/create', [PandaDocController::class, 'create'])
            ->withoutMiddleware('auth:sanctum');


        Route::post('invite', [InvitationController::class, 'invite']);
    });

    require __DIR__ . '/auth.php';

    Route::post('/pandadoc/webhook', [PandaDocController::class, 'handleWebhook']);
});


