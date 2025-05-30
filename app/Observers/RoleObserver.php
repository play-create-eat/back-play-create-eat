<?php

namespace App\Observers;

use App\Services\RolesPermissionsConfigService;
use Spatie\Permission\Models\Role;

class RoleObserver
{
    private RolesPermissionsConfigService $configService;

    public function __construct(RolesPermissionsConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function created(Role $role): void
    {
        try {
            \Log::info("Role created: {$role->name} (guard: {$role->guard_name})");

            $result = $this->configService->addRole(
                $role->name,
                $role->guard_name,
                $role->permissions->pluck('name')->toArray()
            );

            \Log::info("Config add result: " . ($result ? 'success' : 'failed'));
        } catch (\Exception $e) {
            \Log::error("Error in RoleObserver::created: " . $e->getMessage());
        }
    }

    public function updated(Role $role): void
    {
        try {
            if ($role->wasChanged('name')) {
                \Log::info("Role updated: {$role->getOriginal('name')} -> {$role->name}");

                $result = $this->configService->updateRole(
                    $role->getOriginal('name'),
                    $role->name,
                    $role->guard_name,
                    $role->permissions->pluck('name')->toArray()
                );

                \Log::info("Config update result: " . ($result ? 'success' : 'failed'));
            } else {
                // Only permissions were changed
                $result = $this->configService->updateRolePermissions(
                    $role->name,
                    $role->guard_name,
                    $role->permissions->pluck('name')->toArray()
                );

                \Log::info("Config permissions update result: " . ($result ? 'success' : 'failed'));
            }
        } catch (\Exception $e) {
            \Log::error("Error in RoleObserver::updated: " . $e->getMessage());
        }
    }

    public function deleted(Role $role): void
    {
        try {
            \Log::info("Role deleted from database: {$role->name} (guard: {$role->guard_name})");

            // Recreate entire config file from current database state
            $result = $this->configService->deleteRole($role->name, $role->guard_name);

            \Log::info("Config recreation result: " . ($result ? 'success' : 'failed'));
        } catch (\Exception $e) {
            \Log::error("Error in RoleObserver::deleted: " . $e->getMessage());
        }
    }
}
