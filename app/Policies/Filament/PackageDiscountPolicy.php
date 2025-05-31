<?php

namespace App\Policies\Filament;

use Illuminate\Auth\Access\Response;

class PackageDiscountPolicy
{
    public function viewPackageDiscounts($admin): Response
    {
        return $admin->hasPermissionTo('view-package-discounts')
            ? Response::allow()
            : Response::deny('You do not have permission to view package discounts.');
    }
}
