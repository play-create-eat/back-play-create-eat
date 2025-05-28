<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class CelebrationFeaturePolicy
{
    public function viewCelebrationFeatures(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-celebration-features')
            ? Response::allow()
            : Response::deny('You do not have permission to view celebration features.');
    }
}
