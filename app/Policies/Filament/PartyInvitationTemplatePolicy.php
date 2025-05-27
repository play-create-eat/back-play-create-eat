<?php

namespace App\Policies\Filament;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;

class PartyInvitationTemplatePolicy
{
    public function viewPartyInvitationTemplates(Admin $admin): Response
    {
        return $admin->hasPermissionTo('view-party-invitation-templates')
            ? Response::allow()
            : Response::deny('You do not have permission to view party invitation templates.');
    }
}
