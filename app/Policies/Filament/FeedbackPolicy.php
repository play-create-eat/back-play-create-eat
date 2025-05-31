<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class FeedbackPolicy
{
    public function viewFeedbacks(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-feedbacks')
            ? Response::allow()
            : Response::deny('You do not have permission to view feedbacks.');
    }
}
