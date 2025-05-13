<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'menu_item_id', 'audience', 'quantity', 'child_name'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(CartItemModifier::class);
    }
}
