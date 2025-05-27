<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class CelebrationPolicy
{
    public function viewCelebrations(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-celebrations')
            ? Response::allow()
            : Response::deny('You do not have permission to view celebrations.');
    }

    public function updateCelebrations(Admin $admin): Response
    {
        return $admin->hasPermissionTo('update-celebration')
            ? Response::allow()
            : Response::deny('You do not have permission to view celebrations.');
    }
}
