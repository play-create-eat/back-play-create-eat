<?php

use App\Http\Controllers\Web\CelebrationBillController;
use Illuminate\Support\Facades\Route;

Route::get('celebration/{celebration}/print-bill/{adminId?}', [CelebrationBillController::class, 'printBill'])
    ->name('celebration.print-bill')
    ->middleware(['signed']);


Route::get('/celebration/{celebration}/print-menu', [CelebrationBillController::class, 'printMenu'])
    ->name('celebration.print-menu')
    ->middleware('signed');
