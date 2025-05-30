<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSync extends Command
{
    protected $signature = 'permission:sync {--force : Force sync without confirmation}';
    protected $description = 'Sync permissions and roles from config file to database';

    public function handle(): void
    {
        if (!$this->option('force') && !$this->confirm('This will sync all permissions and roles. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->syncPermissions();
        $this->syncRoles();

        $this->info('All permissions and roles synced successfully!');
    }

    private function syncPermissions(): void
    {
        $permissions = config('roles-permissions.permissions', []);

        $this->info("Syncing " . count($permissions) . " permissions...");

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate([
                'name' => $permissionData['name'],
                'guard_name' => $permissionData['guard_name'] ?? 'admin',
            ]);

            $this->line("✓ {$permissionData['name']}");
        }
    }

    private function syncRoles(): void
    {
        $roles = config('roles-permissions.roles', []);

        $this->info("Syncing " . count($roles) . " roles...");

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate([
                'name' => $roleData['name'],
                'guard_name' => $roleData['guard_name'] ?? 'admin',
            ]);

            // Assign permissions to role
            if (isset($roleData['permissions'])) {
                if ($roleData['permissions'] === '*') {
                    // Assign all permissions
                    $role->syncPermissions(Permission::where('guard_name', $role->guard_name)->get());
                } else {
                    // Assign specific permissions
                    $role->syncPermissions($roleData['permissions']);
                }
            }

            $this->line("✓ {$roleData['name']}");
        }
    }
}
