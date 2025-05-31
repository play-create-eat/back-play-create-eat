<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use App\Models\User;

class ProductPackagePolicy
{
    public function viewProductPackages(Admin $admin): bool
    {
        return $admin->hasPermissionTo('view-product-packages');
    }
}
