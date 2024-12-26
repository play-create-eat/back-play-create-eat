<?php

use App\Http\Controllers\Api\v1\InvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('user', function (Request $request) {
            return response()->json($request->user());
        });

        Route::post('invite', [InvitationController::class, 'invite']);
    });

    require __DIR__.'/auth.php';
});


