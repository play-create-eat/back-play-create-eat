<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class PassPolicy
{
    public function refundPass(Admin $admin): Response
    {
        return $admin->hasPermissionTo('refund-pass')
            ? Response::allow()
            : Response::deny('You do not have permission to refund pass.');
    }

    public function refundUsedPass(Admin $admin): Response
    {
        return $admin->hasPermissionTo('refund-used-tickets')
            ? Response::allow()
            : Response::deny('You do not have permission to refund used tickets.');
    }
}
