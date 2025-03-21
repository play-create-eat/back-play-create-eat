<?php


use App\Http\Controllers\Api\v1\CakeController;
use App\Http\Controllers\Api\v1\CelebrationController;
use App\Http\Controllers\Api\v1\InviteController;
use App\Http\Controllers\Api\v1\MenuController;
use App\Http\Controllers\Api\v1\PackageController;
use App\Http\Controllers\Api\v1\SlideshowImageController;
use App\Http\Controllers\Api\v1\ThemeController;

Route::prefix('celebration')
    ->controller(CelebrationController::class)
    ->group(function () {
        Route::get('available-slots', 'availableSlots');
        Route::post('', 'store');

        Route::prefix('{celebration}')->group(function () {
            Route::post('package', 'package');
            Route::post('guests-count', 'guestsCount');
            Route::post('slot', 'slot');
            Route::post('theme', 'theme');
            Route::post('cake', 'cake');
            Route::post('menu', 'menu');
            Route::post('photographer-and-photo-album', 'photographerAndAlbum');
            Route::get('invitations', 'invitations');
        });
    });

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
Route::post('/slideshow/upload', [SlideshowImageController::class, 'uploadPhotos']);
Route::get('/slideshow/{celebration}', [SlideshowImageController::class, 'getPhotos']);
Route::delete('/slideshow/photo', [SlideshowImageController::class, 'deletePhoto']);
Route::get('/slideshow/generate/{celebration}', [SlideshowImageController::class, 'generateSlideshow']);

// Step 10: Confirm & Proceed to Payment
Route::post('/celebrations/confirm', [CelebrationController::class, 'confirm']);
Route::post('/celebrations/pay', [CelebrationController::class, 'pay']);

