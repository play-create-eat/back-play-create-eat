<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionPopulate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:roles-permission-populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update current roles and permissions config using existing roles and permissions in the database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $configPath = config_path('roles_permissions.php');

        if (File::exists($configPath)) {
            if (!$this->confirm('Config file already exists. Do you want to overwrite it?')) {
                $this->info('Export cancelled.');
                return;
            }
        }

        $this->info('Exporting permissions and roles from database...');
        $permissions = $this->exportPermissions();
        $roles = $this->exportRoles();

        $config = [
            'permissions' => $permissions,
            'roles'       => $roles,
        ];

        if ($this->writeConfigFile($config)) {
            $this->info('✅ Roles & permissions configuration exported successfully!');
            $this->info("📁 File created at: $configPath");
            $this->info("📊 Exported: " . count($permissions) . " permissions, " . count($roles) . " roles");
        } else {
            $this->error('❌ Failed to export Roles & permissions configuration.');
        }
    }

    private function exportPermissions(): array
    {
        return Permission::all()->map(function ($permission) {
            return [
                'name'  => $permission->name,
                'guard_name' => $permission->guard_name,
            ];
        })->toArray();
    }

    private function exportRoles(): array
    {
        return Role::with('permissions')->get()->map(function ($role) {
            $permissions = $role->permissions->pluck('name')->toArray();

            $allPermissions = Permission::where('guard_name', $role->guard_name)->pluck('name')->toArray();
            $hasAllPermissions = empty(array_diff($allPermissions, $permissions));

            return [
                'name'        => $role->name,
                'guard_name'  => $role->guard_name,
                'permissions' => $hasAllPermissions ? '*' : $permissions,
            ];
        })->toArray();
    }

    private function writeConfigFile(array $config): bool
    {
        $content = $this->generateConfigContent($config);
        return File::put(config_path('roles-permissions.php'), $content) !== false;
    }

    private function generateConfigContent(array $config): string
    {
        $permissions = $this->formatPermissionsArray($config['permissions']);
        $roles = $this->formatRolesArray($config['roles']);

        return <<<PHP
            <?php

            return [
                'permissions' => $permissions,

                'roles' => $roles,
            ];
            PHP;
    }

    private function formatPermissionsArray(array $permissions): string
    {
        $result = "[\n";

        foreach ($permissions as $permission) {
            $result .= "        [\n";
            $result .= "            'name' => '{$permission['name']}',\n";
            $result .= "            'guard_name' => '{$permission['guard_name']}',\n";
            $result .= "        ],\n";
        }

        $result .= "    ]";
        return $result;
    }

    private function formatRolesArray(array $roles): string
    {
        $result = "[\n";

        foreach ($roles as $role) {
            $result .= "        [\n";
            $result .= "            'name' => '{$role['name']}',\n";
            $result .= "            'guard_name' => '{$role['guard_name']}',\n";

            if ($role['permissions'] === '*') {
                $result .= "            'permissions' => '*', // All permissions\n";
            } else {
                $result .= "            'permissions' => [\n";
                foreach ($role['permissions'] as $permission) {
                    $result .= "                '$permission',\n";
                }
                $result .= "            ],\n";
            }

            $result .= "        ],\n";
        }

        $result .= "    ]";
        return $result;
    }
}
