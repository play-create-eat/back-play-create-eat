<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request, Celebration $celebration, CartService $cartService)
    {
        $validated = $request->validate([
            'menu_items'                         => 'required|array',
            'menu_items.*.menu_item_id'          => 'required|exists:menu_items,id',
            'menu_items.*.audience'              => 'required|in:children,parents',
            'menu_items.*.quantity'              => 'nullable|integer|min:1',
            'menu_items.*.modifier_option_ids'   => 'nullable|array',
            'menu_items.*.modifier_option_ids.*' => 'exists:modifier_options,id',
            'menu_items.*.child_name'            => 'nullable|string|max:255',
            'current_step'                       => 'required|integer',
        ]);

        $cart = $cartService->save($celebration, $validated['menu_items']);

        $cartService->finalize($cart);

        $celebration->update(['current_step' => $validated['current_step']]);

        return response()->json([
            'message' => 'Cart updated successfully.',
            'menu'    => $cart->items->groupBy('audience')->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'id'             => $item->menuItem->id,
                        'name'           => $item->menuItem->name,
                        'price'          => $item->menuItem->price,
                        'audience'       => $item->audience,
                        'quantity'       => $item->quantity,
                        'image'          => $item->menuItem->getFirstMediaUrl('menu_item_images'),
                        'tags'           => $item->menuItem->tags->map(fn($tag) => [
                            'id'    => $tag->id,
                            'name'  => $tag->name,
                            'color' => $tag->color
                        ]),
                        'modifierGroups' => $item->menuItem->modifierGroups->map(function ($group) {
                            return [
                                'id'        => $group->id,
                                'title'     => $group->title,
                                'minAmount' => $group->min_amount,
                                'maxAmount' => $group->max_amount,
                                'required'  => $group->required,
                                'options'   => $group->options->map(fn($opt) => [
                                    'id'            => $opt->id,
                                    'name'          => $opt->name,
                                    'price'         => $opt->price,
                                    'nutritionInfo' => $opt->nutrition_info
                                ])
                            ];
                        })
                    ];
                });
            })
        ]);
    }

    public function show(Celebration $celebration)
    {
        $cart = $celebration->cart()->with([
            'items.menuItem.tags',
            'items.menuItem.modifierGroups.options',
            'items.modifiers.modifierOption'
        ])->first();

        return response()->json([
            'cart' => $cart,
        ]);
    }

    public function finalize(Celebration $celebration, CartService $cartService)
    {
        $cart = $celebration->cart;
        if (!$cart) {
            return response()->json(['message' => 'No cart found'], 404);
        }

        $cartService->finalize($cart);

        return response()->json(['message' => 'Menu finalized and saved to celebration.']);
    }
}
