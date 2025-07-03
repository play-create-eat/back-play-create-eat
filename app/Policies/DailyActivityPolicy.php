<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\DailyActivity;
use Illuminate\Auth\Access\HandlesAuthorization;

class DailyActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermissionTo('view-daily-activities') || $admin->hasPermissionTo('manage-daily-activities');
    }

    public function view(Admin $admin, DailyActivity $dailyActivity): bool
    {
        return $admin->hasPermissionTo('view-daily-activities') || $admin->hasPermissionTo('manage-daily-activities');
    }

    public function create(Admin $admin): bool
    {
        return $admin->hasPermissionTo('create-daily-activities') || $admin->hasPermissionTo('manage-daily-activities');
    }

    public function update(Admin $admin, DailyActivity $dailyActivity): bool
    {
        return $admin->hasPermissionTo('edit-daily-activities') || $admin->hasPermissionTo('manage-daily-activities');
    }

    public function delete(Admin $admin, DailyActivity $dailyActivity): bool
    {
        return $admin->hasPermissionTo('delete-daily-activities') || $admin->hasPermissionTo('manage-daily-activities');
    }

    public function deleteAny(Admin $admin): bool
    {
        return $admin->hasPermissionTo('delete-daily-activities') || $admin->hasPermissionTo('manage-daily-activities');
    }
}