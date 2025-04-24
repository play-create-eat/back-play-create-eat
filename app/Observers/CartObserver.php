<?php

namespace App\Observers;

use App\Models\Cart;

class CartObserver
{
    public function created(Cart $cart): void
    {
        $this->updateCelebrationTotalAmount($cart);
    }

    public function updated(Cart $cart): void
    {
        $this->updateCelebrationTotalAmount($cart);
    }

    public function deleted(Cart $cart): void
    {
        if ($cart->celebration) {
            $cart->celebration->update([
                'total_amount' => $cart->celebration->total_amount - $cart->total_price
            ]);
        }
    }

    private function updateCelebrationTotalAmount(Cart $cart): void
    {
        if ($cart->celebration) {
            $oldTotal = $cart->getOriginal('total_price') ?? 0;
            $newTotal = $cart->total_price;

            $cart->celebration->update([
                'total_amount' => $cart->celebration->total_amount - $oldTotal + $newTotal
            ]);
        }

    }
}
