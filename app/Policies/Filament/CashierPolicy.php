<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CashierPolicy
{
    use HandlesAuthorization;

    public function viewWalletBalance(Admin $admin): Response
    {
        return $admin->hasPermissionTo('cashier-view-wallet')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');
    }

    public function topUpWallet(Admin $admin): Response
    {
        return $admin->hasPermissionTo('cashier-top-up-wallet')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');;
    }

    public function viewTickets(Admin $admin): Response
    {
        return $admin->hasPermissionTo('cashier-view-tickets')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');;
    }

    public function buyTickets(Admin $admin): Response
    {
        return $admin->hasPermissionTo('cashier-buy-tickets')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');;
    }

    public function viewPasses(Admin $admin): Response
    {
        return $admin->hasPermissionTo('cashier-view-passes')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');;
    }

    public function manageCelebrationChildren(Admin $admin): Response
    {
        return $admin->hasPermissionTo('cashier-manage-celebration-children')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');;
    }

    public function payCelebration(Admin $admin): Response
    {
        return $admin->hasPermissionTo('cashier-pay-celebration')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');;
    }
}
