<?php

namespace Database\Seeders;

use App\Models\Celebration;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuTag;
use App\Models\MenuType;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Sharing Table',
            'Individual Box',
            'Parents Menu'
        ];

        $menuTypes = collect($types)->mapWithKeys(function ($name) {
            return [$name => MenuType::firstOrCreate(['title' => $name])];
        });

        $categories = [
            ['name' => 'Pizza', 'type' => 'Sharing Table'],
            ['name' => 'Boxes', 'type' => 'Individual Box'],
            ['name' => 'Salads', 'type' => 'Parents Menu'],
            ['name' => 'Main Dishes', 'type' => 'Parents Menu'],
            ['name' => 'Appetizers', 'type' => 'Sharing Table'],
        ];

        $menuCategories = collect($categories)->mapWithKeys(function ($cat) use ($menuTypes) {
            return [$cat['name'] => MenuCategory::firstOrCreate([
                'name' => $cat['name'],
                'menu_type_id' => $menuTypes[$cat['type']]->id,
            ])];
        });

        $tagsData = [
            ['name' => 'Vegetarian', 'color' => '#4CAF50'],
            ['name' => 'Spicy', 'color' => '#FF5722'],
            ['name' => 'Healthy', 'color' => '#8BC34A'],
            ['name' => 'Kids Favorite', 'color' => '#FFC107'],
        ];
        $tags = collect($tagsData)->mapWithKeys(fn($tag) => [$tag['name'] => MenuTag::firstOrCreate($tag)]);

        $modifierPresets = [
            [
                'title' => 'Main Course',
                'min_amount' => 1,
                'max_amount' => 1,
                'required' => true,
                'options' => ['Nuggets', 'Mini Burger', 'Grilled Cheese']
            ],
            [
                'title' => 'Side Dish',
                'min_amount' => 1,
                'max_amount' => 2,
                'required' => true,
                'options' => ['Fries', 'Mashed Potatoes', 'Steamed Rice']
            ],
            [
                'title' => 'Dessert',
                'min_amount' => 0,
                'max_amount' => 1,
                'required' => false,
                'options' => ['Ice Cream', 'Chocolate Cake']
            ]
        ];

        $modifierGroups = collect($modifierPresets)->map(function ($preset) {
            $group = ModifierGroup::create([
                'title' => $preset['title'],
                'min_amount' => $preset['min_amount'],
                'max_amount' => $preset['max_amount'],
                'required' => $preset['required'],
            ]);

            foreach ($preset['options'] as $name) {
                ModifierOption::create([
                    'modifier_group_id' => $group->id,
                    'name' => $name,
                    'price' => rand(0, 20),
                    'nutrition_info' => json_encode([
                        'calories' => rand(100, 300),
                        'protein' => rand(5, 20)
                    ])
                ]);
            }

            return $group;
        });

        $menuItems = collect();
        foreach (range(1, 10) as $i) {
            $cat = $menuCategories->values()->random();
            $item = MenuItem::create([
                'name' => fake()->word() . ' ' . fake()->randomElement(['Box', 'Plate', 'Bites']),
                'description' => fake()->sentence(),
                'price' => rand(100, 200),
                'menu_type_id' => $cat->menuType->id,
                'menu_category_id' => $cat->id,
            ]);

            $item->tags()->sync($tags->random(rand(0, min(2, $tags->count())))->pluck('id'));
            $item->modifierGroups()->sync($modifierGroups->random(rand(1, $modifierGroups->count()))->pluck('id'));
            $menuItems->push($item);
        }
    }
}
