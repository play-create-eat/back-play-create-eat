<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accessWallet = Permission::create(['name' => 'access wallet']);
        $inviteMembers = Permission::create(['name' => 'invite members']);
        $mainParent = Role::create(['name' => 'Main Parent']);
        $mainParent->givePermissionTo([$accessWallet, $inviteMembers]);

        Role::create(['name' => 'Second Parent']);
        Role::create(['name' => 'Nanny']);
        Role::create(['name' => 'Relative']);    }
}
