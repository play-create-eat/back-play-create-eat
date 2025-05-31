<?php

namespace App\Services;

use App\Models\Celebration;
use Carbon\Carbon;

class CelebrationPricingService
{
    /**
     * Calculate the total celebration price
     *
     * @param Celebration $celebration
     * @param int|null $overrideChildrenCount Optional override for children count
     * @return array Returns both the total cost and breakdown
     */
    public function calculateTotalPrice(Celebration $celebration, ?int $overrideChildrenCount = null): array
    {
        if (!$celebration->relationLoaded('package') ||
            !$celebration->relationLoaded('cake') ||
            !$celebration->relationLoaded('features') ||
            !$celebration->relationLoaded('cart')) {
            $celebration->load([
                'child',
                'cake',
                'package',
                'theme',
                'menuItems',
                'features',
                'cart.items.menuItem.tags',
                'cart.items.modifiers.modifierOption'
            ]);
        }

        $childrenCount = $overrideChildrenCount ?? $celebration->children_count;

        $packagePrice = Carbon::parse($celebration->celebration_date)->isWeekday()
            ? $celebration->package->weekday_price
            : $celebration->package->weekend_price;

        $basePackageCost = ($packagePrice * 100) * $childrenCount;

        $cakeCost = 0;
        if ($celebration->cake && $celebration->cake_weight) {
            $cakeCost = $celebration->cake->price_per_kg * 100 * $celebration->cake_weight;
        }

        // Calculate menu cost (only for parents audience)
        $menuCost = 0;
        if ($celebration->cart) {
            $menuCost = $celebration->cart->items
                ->where('audience', 'parents')
                ->sum(function ($item) {
                    $base = $item->menuItem->cents_price * $item->quantity;
                    $mods = $item->modifiers->sum(fn($mod) => $mod->modifierOption->cents_price ?? 0) * $item->quantity;
                    return $base + $mods;
                });
        }

        // Calculate features cost
        $featuresCost = $celebration->features->sum('cents_price');

        // Calculate total cost
        $totalCost = $basePackageCost + $cakeCost + $menuCost + $featuresCost;

        return [
            'total_cost' => $totalCost,
            'breakdown' => [
                'package_price' => $packagePrice,
                'base_package_cost' => $basePackageCost,
                'cake_cost' => $cakeCost,
                'menu_cost' => $menuCost,
                'features_cost' => $featuresCost,
                'children_count' => $childrenCount
            ]
        ];
    }

    /**
     * Recalculate and update celebration price
     *
     * @param Celebration $celebration
     * @param int|null $overrideChildrenCount Optional override for children count
     * @return array
     */
    public function recalculateAndUpdate(Celebration $celebration, ?int $overrideChildrenCount = null): array
    {
        $pricing = $this->calculateTotalPrice($celebration, $overrideChildrenCount);

        // Update children count if overridden
        if ($overrideChildrenCount !== null) {
            $celebration->children_count = $overrideChildrenCount;
        }

        // Update total amount
        $celebration->total_amount = $pricing['total_cost'];
        $celebration->save();

        return $pricing;
    }

    /**
     * Get price breakdown for display purposes (in AED)
     *
     * @param array $breakdown
     * @return array
     */
    public function formatBreakdownForDisplay(array $breakdown): array
    {
        return [
            'package_price' => $breakdown['package_price'],
            'base_package_cost' => $breakdown['base_package_cost'] / 100,
            'cake_cost' => $breakdown['cake_cost'] / 100,
            'menu_cost' => $breakdown['menu_cost'] / 100,
            'features_cost' => $breakdown['features_cost'] / 100,
            'children_count' => $breakdown['children_count']
        ];
    }
}
