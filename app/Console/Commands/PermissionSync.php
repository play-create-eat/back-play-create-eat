<?php

namespace App\Console\Commands;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions and roles and assign all permissions to super admin';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        foreach ($this->permissions() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
            ]);
        }

        foreach ($this->roles() as $role) {
            $dbRole = Role::firstOrCreate(['name' => $role]);
            if ($role === RoleEnum::SUPER_ADMIN) {
                $dbRole->syncPermissions(Permission::all());
            }
        }

        $this->info('All permissions synced');
    }

    private function permissions(): array
    {
        return $this->modelPermissions();
    }

    private function modelPermissions(): array
    {
        $model = new User();

        $table = $model->getTable();

        return [
            "view $table",
            "create $table",
            "edit $table",
            "delete $table",
        ];

    }

    private static function roles(): array
    {
        return RoleEnum::values();
    }
}
