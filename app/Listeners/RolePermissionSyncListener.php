<?php

namespace App\Listeners;

use App\Services\RolesPermissionsConfigService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RolePermissionSyncListener
{
    private RolesPermissionsConfigService $configService;

    public function __construct(RolesPermissionsConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            \Log::info("Role permission sync event triggered");

            // Always recreate the entire config to ensure accuracy
            $result = $this->configService->exportCurrentDatabase();

            \Log::info("Config recreation from permission sync: " . ($result ? 'success' : 'failed'));
        } catch (\Exception $e) {
            \Log::error("Error in RolePermissionSyncListener: " . $e->getMessage());
        }
    }
}
