<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class ThemePolicy
{
    public function viewThemes(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-themes')
            ? Response::allow()
            : Response::deny('You do not have permission to view packages.');
    }

    public function updateTheme(Admin $admin): Response
    {
        return $admin->hasPermissionTo('update-themes')
            ? Response::allow()
            : Response::deny('You do not have permission to view packages.');
    }
}
