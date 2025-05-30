<?php

namespace App\Observers;

use App\Services\RolesPermissionsConfigService;
use Spatie\Permission\Models\Permission;

class PermissionObserver
{
    private RolesPermissionsConfigService $configService;

    public function __construct(RolesPermissionsConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function created(Permission $permission): void
    {
        try {
            \Log::info("Permission created: {$permission->name} (guard: {$permission->guard_name})");

            $result = $this->configService->addPermission(
                $permission->name,
                $permission->guard_name,
            );

            \Log::info("Config add result: " . ($result ? 'success' : 'failed'));
        } catch (\Exception $e) {
            \Log::error("Error in PermissionObserver::created: " . $e->getMessage());
        }
    }

    public function updated(Permission $permission): void
    {
        try {
            if ($permission->wasChanged('name')) {
                \Log::info("Permission updated: {$permission->getOriginal('name')} -> {$permission->name}");

                $result = $this->configService->updatePermission(
                    $permission->getOriginal('name'),
                    $permission->name,
                    $permission->guard_name,
                );

                \Log::info("Config update result: " . ($result ? 'success' : 'failed'));
            }
        } catch (\Exception $e) {
            \Log::error("Error in PermissionObserver::updated: " . $e->getMessage());
        }
    }

    public function deleted(Permission $permission): void
    {
        try {
            \Log::info("Permission deleted from database: {$permission->name} (guard: {$permission->guard_name})");

            // Recreate entire config file from current database state
            $result = $this->configService->deletePermission($permission->name, $permission->guard_name);

            \Log::info("Config recreation result: " . ($result ? 'success' : 'failed'));
        } catch (\Exception $e) {
            \Log::error("Error in PermissionObserver::deleted: " . $e->getMessage());
        }
    }
}
