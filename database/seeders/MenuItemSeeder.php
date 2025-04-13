<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\MenuType;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MenuItem::create([
            'name'             => 'Sharing Table',
            'description'      => 'Menu Item created for empty sharing table for children.',
            'price'            => 0,
            'menu_category_id' => null,
            'menu_type_id'     => MenuType::where('title', 'Sharing Table')->first()->id,
        ]);
    }
}
