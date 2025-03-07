<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuCategory;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|exists:menu_categories,title'
        ]);

        $category = MenuCategory::where('title', $validated['category'])
            ->with(['items'])
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json([
            'title' => $category->title,
            'data'  => $category->items->map(function ($item) {
                return [
                    'id'             => $item->id,
                    'name'           => $item->name,
                    'price'          => $item->price,
                    'image'          => $item->getFirstMediaUrl('menu_images'),
                    'description'    => $item->description,
                    'tags'           => $item->tags->map(fn($tag) => [
                        'id'    => $tag->id,
                        'name'  => $tag->name,
                        'color' => $tag->color
                    ]),
                    'modifierGroups' => $item->modifierGroups->map(fn($group) => [
                        'id'        => $group->id,
                        'title'      => $group->title,
                        'minAmount' => $group->min_amount,
                        'maxAmount' => $group->max_amount,
                        'required'  => $group->required,
                        'options'   => $group->options->map(fn($option) => [
                            'id'            => $option->id,
                            'name'          => $option->name,
                            'price'         => $option->price,
                            'nutritionInfo' => $option->nutrition_info
                        ])
                    ])
                ];
            })
        ]);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu->load('meals.options'));
    }
}
