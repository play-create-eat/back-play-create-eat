<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\News;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermissionTo('view-news') || $admin->hasPermissionTo('manage-news');
    }

    public function view(Admin $admin, News $news): bool
    {
        return $admin->hasPermissionTo('view-news') || $admin->hasPermissionTo('manage-news');
    }

    public function create(Admin $admin): bool
    {
        return $admin->hasPermissionTo('create-news') || $admin->hasPermissionTo('manage-news');
    }

    public function update(Admin $admin, News $news): bool
    {
        return $admin->hasPermissionTo('edit-news') || $admin->hasPermissionTo('manage-news');
    }

    public function delete(Admin $admin, News $news): bool
    {
        return $admin->hasPermissionTo('delete-news') || $admin->hasPermissionTo('manage-news');
    }

    public function deleteAny(Admin $admin): bool
    {
        return $admin->hasPermissionTo('delete-news') || $admin->hasPermissionTo('manage-news');
    }
}
