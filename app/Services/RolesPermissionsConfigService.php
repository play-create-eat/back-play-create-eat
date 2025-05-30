<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsConfigService
{
    private string $configPath;

    public function __construct()
    {
        $this->configPath = config_path('roles-permissions.php');
    }

    public function addPermission(string $name, string $guardName = 'admin'): bool
    {
        try {
            $config = $this->getConfig();

            // Check if permission already exists
            foreach ($config['permissions'] as $permission) {
                if ($permission['name'] === $name && $permission['guard_name'] === $guardName) {
                    \Log::info("Permission already exists in config: {$name}");
                    return true; // Return true if already exists
                }
            }

            $config['permissions'][] = [
                'name' => $name,
                'guard_name' => $guardName,
            ];

            return $this->writeConfig($config);
        } catch (\Exception $e) {
            \Log::error("Error adding permission to config: " . $e->getMessage());
            return false;
        }
    }

    public function updatePermission(string $oldName, string $newName, string $guardName = 'admin'): bool
    {
        try {
            $config = $this->getConfig();

            // Update permission in permissions array
            foreach ($config['permissions'] as &$permission) {
                if ($permission['name'] === $oldName && $permission['guard_name'] === $guardName) {
                    $permission['name'] = $newName;
                    break;
                }
            }

            // Update permission in all roles that use it
            foreach ($config['roles'] as &$role) {
                if (is_array($role['permissions'])) {
                    $key = array_search($oldName, $role['permissions']);
                    if ($key !== false) {
                        $role['permissions'][$key] = $newName;
                    }
                }
            }

            return $this->writeConfig($config);
        } catch (\Exception $e) {
            \Log::error("Error updating permission in config: " . $e->getMessage());
            return false;
        }
    }

    public function deletePermission(string $name, string $guardName = 'admin'): bool
    {
        \Log::info("Permission deleted, recreating config from database: {$name}");
        return $this->exportCurrentDatabase();
    }

    public function addRole(string $name, string $guardName = 'admin', array $permissions = []): bool
    {
        try {
            $config = $this->getConfig();

            // Check if role already exists
            foreach ($config['roles'] as $role) {
                if ($role['name'] === $name && $role['guard_name'] === $guardName) {
                    \Log::info("Role already exists in config: {$name}");
                    return true;
                }
            }

            $config['roles'][] = [
                'name' => $name,
                'guard_name' => $guardName,
                'permissions' => $permissions,
            ];

            return $this->writeConfig($config);
        } catch (\Exception $e) {
            \Log::error("Error adding role to config: " . $e->getMessage());
            return false;
        }
    }

    public function updateRole(string $oldName, string $newName, string $guardName = 'admin', array $permissions = []): bool
    {
        try {
            $config = $this->getConfig();

            foreach ($config['roles'] as &$role) {
                if ($role['name'] === $oldName && $role['guard_name'] === $guardName) {
                    $role['name'] = $newName;
                    $role['permissions'] = $permissions;
                    return $this->writeConfig($config);
                }
            }

            return false;
        } catch (\Exception $e) {
            \Log::error("Error updating role in config: " . $e->getMessage());
            return false;
        }
    }

    public function updateRolePermissions(string $roleName, string $guardName, array $permissions): bool
    {
        try {
            $config = $this->getConfig();

            foreach ($config['roles'] as &$role) {
                if ($role['name'] === $roleName && $role['guard_name'] === $guardName) {
                    // Check if role should have all permissions
                    $allPermissions = collect($config['permissions'])
                        ->where('guard_name', $guardName)
                        ->pluck('name')
                        ->toArray();

                    $hasAllPermissions = empty(array_diff($allPermissions, $permissions));

                    $role['permissions'] = $hasAllPermissions ? '*' : $permissions;
                    return $this->writeConfig($config);
                }
            }

            return false;
        } catch (\Exception $e) {
            \Log::error("Error updating role permissions in config: " . $e->getMessage());
            return false;
        }
    }

    public function deleteRole(string $name, string $guardName = 'admin'): bool
    {
        \Log::info("Role deleted, recreating config from database: {$name}");
        return $this->exportCurrentDatabase();
    }

    private function getConfig(): array
    {
        try {
            if (!File::exists($this->configPath)) {
                \Log::info("Config file doesn't exist, creating empty structure");
                return ['permissions' => [], 'roles' => []];
            }

            // Clear any cached config
            if (function_exists('config')) {
                app('config')->offsetUnset('roles-permissions');
            }

            $config = include $this->configPath;

            if (!is_array($config)) {
                \Log::warning("Config file returned non-array, using empty structure");
                return ['permissions' => [], 'roles' => []];
            }

            // Ensure required keys exist
            if (!isset($config['permissions'])) {
                $config['permissions'] = [];
            }
            if (!isset($config['roles'])) {
                $config['roles'] = [];
            }

            return $config;
        } catch (\Exception $e) {
            \Log::error("Error reading config: " . $e->getMessage());
            return ['permissions' => [], 'roles' => []];
        }
    }

    private function writeConfig(array $config): bool
    {
        try {
            $content = $this->generateConfigContent($config);

            // Ensure directory exists
            $directory = dirname($this->configPath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Write to file
            $result = File::put($this->configPath, $content);

            if ($result === false) {
                \Log::error("Failed to write to config file: {$this->configPath}");
                return false;
            }

            // Clear any cached config
            if (function_exists('config')) {
                app('config')->offsetUnset('roles-permissions');
            }

            \Log::info("Successfully wrote config file");
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to write config: ' . $e->getMessage());
            return false;
        }
    }

    private function generateConfigContent(array $config): string
    {
        $permissions = $this->formatPermissionsArray($config['permissions']);
        $roles = $this->formatRolesArray($config['roles']);

        return <<<PHP
<?php

return [
    'permissions' => {$permissions},

    'roles' => {$roles},
];
PHP;
    }

    private function formatPermissionsArray(array $permissions): string
    {
        if (empty($permissions)) {
            return '[]';
        }

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
        if (empty($roles)) {
            return '[]';
        }

        $result = "[\n";

        foreach ($roles as $role) {
            $result .= "        [\n";
            $result .= "            'name' => '{$role['name']}',\n";
            $result .= "            'guard_name' => '{$role['guard_name']}',\n";

            if ($role['permissions'] === '*') {
                $result .= "            'permissions' => '*', // All permissions\n";
            } elseif (empty($role['permissions'])) {
                $result .= "            'permissions' => [],\n";
            } else {
                $result .= "            'permissions' => [\n";
                foreach ($role['permissions'] as $permission) {
                    $result .= "                '{$permission}',\n";
                }
                $result .= "            ],\n";
            }

            $result .= "        ],\n";
        }

        $result .= "    ]";
        return $result;
    }

    public function exportCurrentDatabase(): bool
    {
        try {
            \Log::info("Recreating config file from current database state");

            $permissions = Permission::all()->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                ];
            })->toArray();

            $roles = Role::with('permissions')->get()->map(function ($role) {
                $permissions = $role->permissions->pluck('name')->toArray();

                // Check if role has all permissions
                $allPermissions = Permission::where('guard_name', $role->guard_name)->pluck('name')->toArray();
                $hasAllPermissions = empty(array_diff($allPermissions, $permissions));

                return [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $hasAllPermissions ? '*' : $permissions,
                ];
            })->toArray();

            $config = [
                'permissions' => $permissions,
                'roles' => $roles,
            ];

            $result = $this->writeConfig($config);

            if ($result) {
                \Log::info("Config file recreated successfully. Permissions: " . count($permissions) . ", Roles: " . count($roles));
            } else {
                \Log::error("Failed to recreate config file");
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error("Error recreating config from database: " . $e->getMessage());
            return false;
        }
    }
}
