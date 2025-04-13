<?php


use App\Http\Controllers\Api\v1\CakeController;
use App\Http\Controllers\Api\v1\CartController;
use App\Http\Controllers\Api\v1\CelebrationController;
use App\Http\Controllers\Api\v1\InviteController;
use App\Http\Controllers\Api\v1\MenuController;
use App\Http\Controllers\Api\v1\PackageController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\SlideshowImageController;
use App\Http\Controllers\Api\v1\ThemeController;

Route::prefix('celebration')
    ->controller(CelebrationController::class)
    ->group(function () {
        Route::get('', 'index');
        Route::post('', 'store');

        Route::prefix('{celebration}')->group(function () {
            Route::post('package', 'package');
            Route::get('timelines', 'timelines');
            Route::post('guests-count', 'guestsCount');
            Route::get('slots', 'slots');
            Route::post('slot', 'slot');
            Route::post('theme', 'theme');
            Route::post('cake', 'cake');
            Route::post('cart', [CartController::class, 'store']);
            Route::get('cart', [CartController::class, 'show']);
            Route::post('cart/finalize', [CartController::class, 'finalize']);
            Route::post('photographer', 'photographer');
            Route::post('album', 'album');
            Route::post('slideshow', 'slideshow');
            Route::delete('slideshow/{media}', [SlideshowImageController::class, 'destroy']);
            Route::post('invitations/{template}', [InviteController::class, 'generate']);
            Route::post('payments', [PaymentController::class, 'store']);
        });
    });

Route::get('invitation-templates', [InviteController::class, 'index']);

Route::get('/themes', [ThemeController::class, 'index']);

Route::get('/packages', [PackageController::class, 'index']);
Route::get('/packages/{package}', [PackageController::class, 'show']);

Route::get('/available-slots', [CelebrationController::class, 'getAvailableSlots']);

// Step 5: Choose Theme
Route::get('/themes', [ThemeController::class, 'index']);
Route::get('/themes/{theme}', [ThemeController::class, 'show']);

// Step 6: Select Cake
Route::get('/cakes', [CakeController::class, 'index']);
Route::get('/cakes/{cake}', [CakeController::class, 'show']);

// Step 7: Select Menu
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menus/{menu}', [MenuController::class, 'show']);

// Step 8: Generate Invitation
Route::post('/invites', [InviteController::class, 'store']);
Route::get('/invites/{celebration}', [InviteController::class, 'show']);

// Step 9: Upload Slideshow Photos
Route::get('/slideshow/{celebration}', [SlideshowImageController::class, 'index']);

// Step 10: Confirm & Proceed to Payment
Route::post('/celebrations/confirm', [CelebrationController::class, 'confirm']);
Route::post('/celebrations/pay', [CelebrationController::class, 'pay']);

