<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class MenuManagementPolicy
{
    public function manageMenu(Admin $admin): Response
    {
        return $admin->hasPermissionTo('manage-menu')
            ? Response::allow()
            : Response::deny('You do not have permission to manage menu.');
    }
}
