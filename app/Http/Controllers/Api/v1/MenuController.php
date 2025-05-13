<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuType;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');

        if ($type) {
            $menuType = MenuType::where('title', $type)
                ->with([
                    'categories.items.tags',
                    'categories.items.media',
                    'categories.items.modifierGroups.options',
                    'categories.items.options'
                ])
                ->first();

            if (!$menuType) {
                return response()->json(['message' => 'Menu type not found'], 404);
            }

            $items = $menuType->categories->flatMap(function ($category) {
                return $category->items->map(function ($item) use ($category) {
                    return array_merge(
                        $this->formatMenuItem($item),
                        ['category' => $category->name]
                    );
                });
            });

            return response()->json([
                'type'  => $menuType->title,
                'items' => $items,
            ]);
        } else {
            $categories = MenuCategory::with([
                'items.tags',
                'items.media',
                'items.modifierGroups.options'
            ])->get();

            $items = $categories->flatMap(function ($category) {
                return $category->items->map(function ($item) use ($category) {
                    return array_merge(
                        $this->formatMenuItem($item),
                        ['category' => $category->name]
                    );
                });
            });

            return response()->json([
                'type'  => 'All',
                'items' => $items,
            ]);
        }
    }

    protected function formatMenuItem($item)
    {
        return [
            'id'             => $item->id,
            'name'           => $item->name,
            'price'          => $item->price * 100,
            'image'          => $item->getFirstMediaUrl('menu_item_images'),
            'description'    => $item->description,
            'options'        => $item->options,
            'tags'           => $item->tags->map(fn($tag) => [
                'id'    => $tag->id,
                'name'  => $tag->name,
                'color' => $tag->color,
            ]),
            'modifierGroups' => $item->modifierGroups->map(fn($group) => [
                'id'        => $group->id,
                'title'     => $group->title,
                'minAmount' => $group->min_amount,
                'maxAmount' => $group->max_amount,
                'required'  => $group->required,
                'options'   => $group->options->map(fn($option) => [
                    'id'            => $option->id,
                    'name'          => $option->name,
                    'price'         => $option->price * 100,
                    'nutritionInfo' => $option->nutrition_info,
                ]),
            ]),
        ];
    }
}
