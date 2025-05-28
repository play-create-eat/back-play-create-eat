<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class PackagePolicy
{
    public function viewPackages(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-packages')
            ? Response::allow()
            : Response::deny('You do not have permission to view packages.');
    }

    public function updatePackage(Admin $admin): Response
    {
        return $admin->hasPermissionTo('update-packages')
            ? Response::allow()
            : Response::deny('You do not have permission to view packages.');
    }
}
