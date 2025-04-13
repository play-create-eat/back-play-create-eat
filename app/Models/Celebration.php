<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Celebration extends Model
{

    // TODO calcuate full celeberation price
    // TODO add notification when need to full payment
    protected $fillable = [
        'user_id',
        'child_id',
        'package_id',
        'theme_id',
        'children_count',
        'parents_count',
        'celebration_date',
        'cake_id',
        'cake_weight',
        'current_step',
        'completed',
        'menu_id',
        'photo_album',
        'total_amount',
        'paid_amount',
        'current_step',
        'min_amount'
    ];

    protected $casts = [
        'celebration_date' => 'datetime',
        'paid_amount'      => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'current_step'     => 'integer'
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function cake(): BelongsTo
    {
        return $this->belongsTo(Cake::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'celebration_menus')
            ->withPivot(['quantity', 'audience'])
            ->withTimestamps();
    }

    public function modifierOptions(): BelongsToMany
    {
        return $this->belongsToMany(ModifierOption::class, 'celebration_menu_modifiers');
    }

    public function slideshow(): HasOne
    {
        return $this->hasOne(SlideshowImage::class);
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(Invite::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function features(): belongsToMany
    {
        return $this->belongsToMany(CelebrationFeature::class)->withTimestamps();
    }

    public function getCartTotalPriceAttribute(): float
    {
        return $this->cart?->items
            ->where('audience', 'parents')
            ->sum(function ($item) {
                $base = $item->menuItem->price * $item->quantity;
                $mods = $item->modifiers->sum(fn($mod) => $mod->modifierOption->price ?? 0) * $item->quantity;
                return $base + $mods;
            }) ?? 0;
    }

}
