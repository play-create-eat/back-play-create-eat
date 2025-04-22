<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['celebration_id'];

    protected $appends = ['total_price'];

    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    protected function totalPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                $total = 0;

                foreach ($this->items as $item) {
                    if ($item->audience === 'parents') {
                        $basePrice = $item->menuItem->cents_price;
                        $total += $basePrice * $item->quantity;

                        foreach ($item->modifiers as $modifier) {
                            $modifierPrice = $modifier->modifierOption->cents_price;
                            $total += $modifierPrice * $item->quantity;
                        }
                    }
                }
                return $total;
            },
        );
    }

}
