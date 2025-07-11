<?php

use App\Http\Controllers\Api\v1\ChildController;
use App\Http\Controllers\Api\v1\DailyActivityController;
use App\Http\Controllers\Api\v1\FamilyController;
use App\Http\Controllers\Api\v1\InvitationController;
use App\Http\Controllers\Api\v1\NewsController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\PandaDocController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ProductPackageController;
use App\Http\Controllers\Api\v1\ProfileController;
use App\Http\Controllers\Api\v1\SurveyController;
use App\Http\Middleware\CheckAuthenticationOrRegistrationId;
use App\Http\Resources\Api\v1\UserResource;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('user', function () {
            return new UserResource(auth()->guard('sanctum')->user()->load(['profile', 'family', 'permissions']));
        });

        Route::get('children', [ChildController::class, 'index'])
            ->withoutMiddleware('auth:sanctum')
            ->middleware(CheckAuthenticationOrRegistrationId::class);

        Route::get('children/{child}', [ChildController::class, 'show'])
            ->withoutMiddleware('auth:sanctum')
            ->middleware(CheckAuthenticationOrRegistrationId::class);

        Route::post('children', [ChildController::class, 'store'])
            ->withoutMiddleware('auth:sanctum')
            ->middleware(CheckAuthenticationOrRegistrationId::class);

        Route::post('children/{child}', [ChildController::class, 'update']);
        Route::delete('children/{child}', [ChildController::class, 'destroy'])
            ->withoutMiddleware('auth:sanctum');

        Route::post('documents', [PandaDocController::class, 'create'])
            ->withoutMiddleware('auth:sanctum');

        Route::get('documents/status/{id}', [PandaDocController::class, 'status'])
            ->withoutMiddleware('auth:sanctum');

        Route::post('invite', [InvitationController::class, 'invite']);

        Route::get('user/family-members', [FamilyController::class, 'members']);
        Route::get('user/family-passes', [FamilyController::class, 'passes']);
        Route::get('user/family-pass-packages', [FamilyController::class, 'passPackages']);

        Route::get('product', [ProductController::class, 'index']);
        Route::post('product/purchase', [ProductController::class, 'purchase']);
        Route::post('product/refund', [ProductController::class, 'refund']);

        Route::get('product-package', [ProductPackageController::class, 'index']);
        Route::post('product-package/purchase', [ProductPackageController::class, 'purchase']);
        Route::post('product-package/refund', [ProductPackageController::class, 'refund']);
        Route::post('product-package/redeem', [ProductPackageController::class, 'redeem']);

        Route::put('profile', [ProfileController::class, 'update']);

        require __DIR__ . '/celebration.php';

        Route::post('payments/{payment}/pay', [PaymentController::class, 'pay']);

        Route::post('payments/{payment}/card-pay', [PaymentController::class, 'cardPay']);

        Route::post('/notifications/test', [NotificationController::class, 'sendTestNotification']);

        // Survey routes (admin access)
        Route::get('surveys', [SurveyController::class, 'index']);
        Route::get('surveys/{survey}', [SurveyController::class, 'show']);
    });

    // Public survey submission route
    Route::post('survey', [SurveyController::class, 'store']);

    Route::post('invite/register-step-1', [InvitationController::class, 'validateStep1']);
    Route::post('invite/register-step-2', [InvitationController::class, 'validateStep2']);
    Route::post('invite/register', [InvitationController::class, 'register']);
    Route::post('pandadoc/webhook', [PandaDocController::class, 'handleWebhook']);
    Route::post('pandadoc/create', [PandaDocController::class, 'create'])
        ->withoutMiddleware('auth:sanctum');

    Route::apiResource('news', NewsController::class)->only(['index', 'show']);

    // Daily Activities routes
    Route::prefix('daily-activities')->group(function () {
        Route::get('/', [DailyActivityController::class, 'index']);
        Route::get('/today', [DailyActivityController::class, 'today']);
        Route::get('/week', [DailyActivityController::class, 'week']);
        Route::get('/categories', [DailyActivityController::class, 'categories']);
        Route::get('/locations', [DailyActivityController::class, 'locations']);
        Route::get('/{dailyActivity}', [DailyActivityController::class, 'show']);
    });

    require __DIR__ . '/auth.php';
    require __DIR__ . '/stripe.php';
    require __DIR__ . '/internal.php';
});

require __DIR__ . '/filament.php';


