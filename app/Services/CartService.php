<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Celebration;
use App\Models\MenuItem;
use DB;

class CartService
{
    public function save(Celebration $celebration, array $menuItems): Cart
    {
        $cart = $celebration->cart()->firstOrCreate();
        $cart->items()->delete();

        foreach ($menuItems as $item) {
            $menuItem = MenuItem::with('type')->findOrFail($item['menu_item_id']);
            $typeName = strtolower($menuItem->type->name ?? '');
            $audience = $item['audience'];

            $isSharingTableForChildren = $typeName === 'sharing table' && $audience === 'children';

            $cartItem = $cart->items()->create([
                'menu_item_id' => $menuItem->id,
                'audience'     => $audience,
                'quantity'     => $isSharingTableForChildren ? 1 : ($item['quantity'] ?? 1),
                'child_name'   => $isSharingTableForChildren ? null : ($item['child_name'] ?? null),
            ]);

            if (!$isSharingTableForChildren && !empty($item['modifier_option_ids'])) {
                foreach ($item['modifier_option_ids'] as $modifierId) {
                    $cartItem->modifiers()->create([
                        'modifier_option_id' => $modifierId
                    ]);
                }
            }
        }

        return $cart->load('items.menuItem.modifierGroups.options', 'items.modifiers.modifierOption');
    }

    public function finalize(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $cart->celebration->menuItems()->attach($item->menu_item_id, [
                'quantity'   => $item->quantity,
                'audience'   => $item->audience,
                'child_name' => $item->child_name,
            ]);

            foreach ($item->modifiers as $mod) {
                DB::table('celebration_menu_modifiers')->insert([
                    'celebration_id'     => $cart->celebration_id,
                    'menu_item_id'       => $item->menu_item_id,
                    'modifier_option_id' => $mod->modifier_option_id,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }

        $cart->delete();
    }
}
