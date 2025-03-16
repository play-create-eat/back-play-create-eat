<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@playcreateeat.ae',
            'password' => Hash::make(config('auth.defaults.super_admin_password')),
        ]);
    }
}
