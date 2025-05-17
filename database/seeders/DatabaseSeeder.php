<?php

namespace Database\Seeders;

use App\Models\Timeline;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'access wallet']);
        Permission::create(['name' => 'invite members']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $mainParent = Role::create(['name' => 'Administrator']);
        $mainParent->givePermissionTo(['access wallet', 'invite members']);

        Role::create(['name' => 'Parent']);
        Role::create(['name' => 'Nanny']);
        Role::create(['name' => 'Relative']);

        $this->call([
            AdminSeeder::class,
            TableSeeder::class,
            MenuSeeder::class,
            PackageSeeder::class,
            PackageFeatureSeeder::class,
            TimelineSeeder::class,
        ]);
    }
}
