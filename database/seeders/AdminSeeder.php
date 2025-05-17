<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminPermissions = [
            'view-dashboard',

            'manage-admins',
            'create-admin',
            'edit-admin',
            'delete-admin',

            'manage-roles',
            'create-role',
            'edit-role',
            'delete-role',

            'manage-permissions',
            'create-permission',
            'edit-permission',
            'delete-permission',

            'manage-tables',
            'create-table',
            'edit-table',
            'delete-table',

            'manage-packages',
            'create-package',
            'edit-package',
            'delete-package',

            'manage-bookings',
            'create-booking',
            'edit-booking',
            'delete-booking',

            'manage-playground',

            'manage-restaurant',

            'manage-parents-zone',

            'manage-users',
            'view-user',
            'edit-user',
            'delete-user',
        ];

        foreach ($adminPermissions as $permission) {
            Permission::create([
                'name'        => $permission,
                'guard_name'  => 'admin',
            ]);
        }

        $superAdminRole = Role::create([
            'name'       => 'super-admin',
            'guard_name' => 'admin',
        ]);

        $managerRole = Role::create([
            'name'       => 'manager',
            'guard_name' => 'admin',
        ]);

        $staffRole = Role::create([
            'name'       => 'staff',
            'guard_name' => 'admin',
        ]);

        $superAdminRole->givePermissionTo(Permission::where('guard_name', 'admin')->get());

        $managerRole->givePermissionTo([
            'view-dashboard',
            'manage-bookings',
            'create-booking',
            'edit-booking',
            'manage-playground',
            'manage-restaurant',
            'manage-parents-zone',
            'view-user',
        ]);

        $staffRole->givePermissionTo([
            'view-dashboard',
            'view-user',
            'manage-bookings',
            'create-booking',
        ]);

        $superAdmin = Admin::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@playcreateeat.ae',
            'password' => Hash::make(config('auth.defaults.super_admin_password', 'password'))
        ]);

        $superAdmin->assignRole($superAdminRole);

        $manager = Admin::create([
            'name'     => 'Manager',
            'email'    => 'manager@playcreateeat.ae',
            'password' => Hash::make(config('auth.defaults.super_admin_password', 'password'))
        ]);

        $manager->assignRole($managerRole);

        $staff = Admin::create([
            'name'     => 'Staff',
            'email'    => 'staff@playcreateeat.ae',
            'password' => Hash::make(config('auth.defaults.super_admin_password', 'password'))
        ]);

        $staff->assignRole($staffRole);
    }
}
