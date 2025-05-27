<?php

namespace App\Providers;

use App\Policies\Filament\AdminPolicy;
use App\Policies\Filament\CashierPolicy;
use App\Policies\Filament\CelebrationFeaturePolicy;
use App\Policies\Filament\CelebrationPolicy;
use App\Policies\Filament\FamilyPolicy;
use App\Policies\Filament\MenuManagementPolicy;
use App\Policies\Filament\PackagePolicy;
use App\Policies\Filament\PartyInvitationTemplatePolicy;
use App\Policies\Filament\PermissionPolicy;
use App\Policies\Filament\ProductPolicy;
use App\Policies\Filament\ProductTypePolicy;
use App\Policies\Filament\RolePolicy;
use App\Policies\Filament\ThemePolicy;
use App\Policies\Filament\UserPolicy;
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

        Gate::define('viewCelebrations', [CelebrationPolicy::class, 'viewCelebrations']);
        Gate::define('updateCelebrations', [CelebrationPolicy::class, 'updateCelebrations']);

        Gate::define('manageMenu', [MenuManagementPolicy::class, 'manageMenu']);

        Gate::define('viewPackages', [PackagePolicy::class, 'viewPackages']);
        Gate::define('updatePackage', [PackagePolicy::class, 'updatePackage']);

        Gate::define('viewThemes', [ThemePolicy::class, 'viewThemes']);
        Gate::define('updateTheme', [ThemePolicy::class, 'updateTheme']);

        Gate::define('viewPartyInvitationTemplates', [PartyInvitationTemplatePolicy::class, 'viewPartyInvitationTemplates']);

        Gate::define('viewCelebrationFeatures', [CelebrationFeaturePolicy::class, 'viewCelebrationFeatures']);

        Gate::define('viewFamilies', [FamilyPolicy::class, 'viewFamilies']);

        Gate::define('viewUsers', [UserPolicy::class, 'viewUsers']);

        Gate::define('viewAdmins', [AdminPolicy::class, 'viewAdmins']);

        Gate::define('viewRoles', [RolePolicy::class, 'viewRoles']);

        Gate::define('viewPermissions', [PermissionPolicy::class, 'viewPermissions']);

        Gate::define('viewProducts', [ProductPolicy::class, 'viewProducts']);

        Gate::define('viewProductTypes', [ProductTypePolicy::class, 'viewProductTypes']);
    }
}
