<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function viewProducts(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-products')
            ? Response::allow()
            : Response::deny('You do not have permission to view products.');
    }
}
