<?php

use App\Http\Controllers\Web\CelebrationBillController;
use Illuminate\Support\Facades\Route;

Route::get('celebration/{celebration}/print-bill/{adminId?}', [CelebrationBillController::class, 'printBill'])
    ->name('celebration.print-bill')
    ->middleware(['signed']);
