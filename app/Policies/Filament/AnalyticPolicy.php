<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class AnalyticPolicy
{
    public function viewTodayAnalytics(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-today-analytics')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');
    }

    public function viewFullAnalytics(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-analytics')
            ? Response::allow()
            : Response::deny('You do not have permission to view wallet balance.');
    }
}
