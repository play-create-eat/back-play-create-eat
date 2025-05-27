<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class ProductTypePolicy
{
    public function viewProductTypes(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-product-types')
            ? Response::allow()
            : Response::deny('You do not have permission to view product types.');
    }
}
