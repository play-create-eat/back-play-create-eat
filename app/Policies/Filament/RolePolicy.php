<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    public function viewRoles(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-roles')
            ? Response::allow()
            : Response::deny('You do not have permission to view roles.');
    }
}
