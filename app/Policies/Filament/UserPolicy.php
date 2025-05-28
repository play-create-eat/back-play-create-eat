<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;


class UserPolicy
{
    public function viewUsers(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-users')
            ? Response::allow()
            : Response::deny('You do not have permission to view users.');
    }
}
