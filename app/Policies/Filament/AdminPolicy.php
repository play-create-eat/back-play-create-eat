<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class AdminPolicy
{
    public function viewAdmins(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-admins')
            ? Response::allow()
            : Response::deny('You do not have permission to view admins.');
    }
}
