<?php

namespace App\Providers;

use App\Policies\Filament\CashierPolicy;
use Gate;
use Illuminate\Support\ServiceProvider;

class PolicyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
        Gate::define('viewWalletBalance', [CashierPolicy::class, 'viewWalletBalance']);
        Gate::define('topUpWallet', [CashierPolicy::class, 'topUpWallet']);
        Gate::define('viewTickets', [CashierPolicy::class, 'viewTickets']);
        Gate::define('buyTickets', [CashierPolicy::class, 'buyTickets']);
        Gate::define('viewPasses', [CashierPolicy::class, 'viewPasses']);
        Gate::define('manageCelebrationChildren', [CashierPolicy::class, 'manageCelebrationChildren']);
        Gate::define('payCelebration', [CashierPolicy::class, 'payCelebration']);
    }
}
