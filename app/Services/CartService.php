<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Celebration;
use App\Models\MenuItem;
use Log;

class CartService
{
    public function save(Celebration $celebration, array $menuItems): Cart
    {
        $cart = $celebration->cart()->firstOrCreate();

        $audiences = collect($menuItems)->pluck('audience')->unique();

        foreach ($audiences as $audience) {
            $cart->items()
                ->where('audience', $audience)
                ->get()
                ->each(function ($item) {
                    $item->modifiers()->delete();
                    $item->delete();
                });
        }

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
        $celebration = $cart->celebration;

        $total = 0;

        $order = $celebration->order()->create([
            'total_price' => 0,
        ]);

        foreach ($cart->items as $item) {
            $orderItem = $order->items()->create([
                'menu_item_id' => $item->menu_item_id,
                'quantity'     => $item->quantity,
                'audience'     => $item->audience,
            ]);

            foreach ($item->modifiers as $mod) {
                $orderItem->modifiers()->create([
                    'order_item_id'     => $orderItem->id,
                    'modifier_option_id' => $mod->modifier_option_id,
                ]);
            }

            if ($item->audience === 'parents') {
                $itemBase = ($item->menuItem->price * 100) * $item->quantity;
                $itemMods = $item->modifiers->sum(fn($m) => $m->modifierOption->price * 100) * $item->quantity;
                $total += $itemBase + $itemMods;
            }
        }

        $order->update([
            'total_price' => $total,
        ]);

        Log::info('Menu total price is: ' . $total);

        $cart->celebration->update([
            'total_amount' => $celebration->total_amount + $total,
        ]);

        $celebration->refresh();

        Log::info('Celebration total amount is: ' . $celebration->total_amount);

        $cart->delete();
    }
}
