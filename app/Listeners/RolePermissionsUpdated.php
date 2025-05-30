<?php

namespace App\Listeners;

use App\Services\RolesPermissionsConfigService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Permission\Models\Role;

readonly class RolePermissionsUpdated
{
    /**
     * Create the event listener.
     */
    public function __construct(private RolesPermissionsConfigService $rolesPermissionsConfigService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if (isset($event->role) && $event->role instanceof Role) {
            $this->rolesPermissionsConfigService->updateRolePermissions(
                $event->role->name,
                $event->role->guard_name,
                $event->role->permissions->pluck('name')->toArray()
            );
        }

    }
}
