<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    public function viewPermissions(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-permissions')
            ? Response::allow()
            : Response::deny('You do not have permission to view permissions.');
    }
}
