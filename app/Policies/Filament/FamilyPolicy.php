<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class FamilyPolicy
{
    public function viewFamilies(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-families')
            ? Response::allow()
            : Response::deny('You do not have permission to view families.');
    }
}
