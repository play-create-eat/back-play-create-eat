<?php

namespace Database\Seeders;

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
        $sharingTable   = MenuType::firstOrCreate(['title' => 'Sharing Table']);
        $individualBox  = MenuType::firstOrCreate(['title' => 'Individual Box']);
        $parentsMenu    = MenuType::firstOrCreate(['title' => 'Parents Menu']);

        $pizzaCat   = MenuCategory::firstOrCreate(['name' => 'Pizza', 'menu_type_id' => $sharingTable->id]);
        $boxCat     = MenuCategory::firstOrCreate(['name' => 'Boxes', 'menu_type_id' => $individualBox->id]);
        $saladCat   = MenuCategory::firstOrCreate(['name' => 'Salads', 'menu_type_id' => $parentsMenu->id]);
        $mainDishCat = MenuCategory::firstOrCreate(['name' => 'Main Dishes', 'menu_type_id' => $parentsMenu->id]);

        $tags = collect([
            ['name' => 'Vegetarian', 'color' => '#4CAF50'],
            ['name' => 'Spicy', 'color' => '#FF5722'],
            ['name' => 'Healthy', 'color' => '#8BC34A'],
            ['name' => 'Kids Favorite', 'color' => '#FFC107'],
        ])->mapWithKeys(fn($tag) => [$tag['name'] => MenuTag::firstOrCreate($tag)]);

        $modifierGroups = collect([
            [
                'title' => 'Main Course',
                'required' => true,
                'min_amount' => 1,
                'max_amount' => 1,
                'options' => ['Nuggets', 'Mini Burger', 'Grilled Cheese']
            ],
            [
                'title' => 'Side Dish',
                'required' => true,
                'min_amount' => 1,
                'max_amount' => 2,
                'options' => ['Fries', 'Mashed Potatoes', 'Steamed Rice']
            ],
            [
                'title' => 'Dessert',
                'required' => false,
                'min_amount' => 0,
                'max_amount' => 1,
                'options' => ['Ice Cream', 'Chocolate Cake']
            ]
        ]);

        $groupModels = collect();

        foreach ($modifierGroups as $groupData) {
            $group = ModifierGroup::create([
                'menu_item_id' => null,
                'title' => $groupData['title'],
                'required' => $groupData['required'],
                'min_amount' => $groupData['min_amount'],
                'max_amount' => $groupData['max_amount'],
            ]);

            foreach ($groupData['options'] as $optionName) {
                ModifierOption::create([
                    'modifier_group_id' => $group->id,
                    'name' => $optionName,
                    'price' => rand(0, 20),
                    'nutrition_info' => json_encode([
                        'calories' => rand(100, 300),
                        'protein'  => rand(5, 20),
                    ]),
                ]);
            }

            $groupModels->push($group);
        }

        foreach (range(1, 5) as $i) {
            $childType = [$sharingTable->id, $individualBox->id][array_rand([0, 1])];
            $category = $childType === $sharingTable->id ? $pizzaCat : $boxCat;

            $item = MenuItem::create([
                'name'             => fake()->word() . ' Box',
                'price'            => rand(80, 150),
                'description'      => fake()->sentence(),
                'menu_type_id'     => $childType,
                'menu_category_id' => $category->id,
            ]);

            if ($tags->count() > 0) {
                $item->tags()->sync($tags->random(rand(0, min(2, $tags->count())))->pluck('id'));
            }

            if ($groupModels->count() > 0) {
                $item->modifierGroups()->sync(
                    $groupModels->shuffle()->take(rand(1, $groupModels->count()))->pluck('id')
                );
            }
        }

        foreach (range(1, 5) as $i) {
            $item = MenuItem::create([
                'name'             => fake()->word() . ' Plate',
                'price'            => rand(120, 200),
                'description'      => fake()->sentence(),
                'menu_type_id'     => $parentsMenu->id,
                'menu_category_id' => $mainDishCat->id,
            ]);

            if ($tags->count() > 0) {
                $item->tags()->sync($tags->random(rand(0, min(2, $tags->count())))->pluck('id'));
            }

            if ($groupModels->count() > 0) {
                $item->modifierGroups()->sync(
                    $groupModels->shuffle()->take(rand(1, $groupModels->count()))->pluck('id')
                );
            }
        }
    }
}
