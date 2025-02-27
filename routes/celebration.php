<?php


use App\Http\Controllers\Api\v1\CakeController;
use App\Http\Controllers\Api\v1\CelebrationController;
use App\Http\Controllers\Api\v1\InviteController;
use App\Http\Controllers\Api\v1\MenuController;
use App\Http\Controllers\Api\v1\PackageController;
use App\Http\Controllers\Api\v1\SlideshowImageController;
use App\Http\Controllers\Api\v1\ThemeController;

Route::get('celebration/available-slots', [CelebrationController::class, 'availableSlots']);

Route::post('celebration', [CelebrationController::class, 'store']);

Route::post('celebration/{celebration}/package', [CelebrationController::class, 'package']);

Route::post('celebration/{celebration}/guests-count', [CelebrationController::class, 'guestsCount']);

Route::post('celebration/{celebration}/slot', [CelebrationController::class, 'slot']);

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
Route::get('/menus', [MenuController::class, 'index']);
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

