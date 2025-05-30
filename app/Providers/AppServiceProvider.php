<?php

namespace App\Providers;

use App\Models\Cart;
use App\Observers\CartObserver;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Cart::observe(CartObserver::class);
        Permission::observe(PermissionObserver::class);
        Role::observe(RoleObserver::class);
    }
}
