<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'paid_amount'
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
            ->withPivot('quantity');
    }

    public function modifierOptions(): BelongsToMany
    {
        return $this->belongsToMany(ModifierOption::class, 'celebration_menu_modifiers');
    }

    public function tables(): HasMany
    {
        return $this->hasMany(TableBooking::class);
    }

    public function calculateMenuPrice()
    {
        return $this->menuItems->sum(function ($menuItem) {
            $basePrice = $menuItem->price * $menuItem->pivot->quantity;

            $modifiersPrice = $menuItem->modifierGroups->sum(function ($modifierGroup) use ($menuItem) {
                return $modifierGroup->options->sum(function ($option) use ($menuItem) {
                    return $option->price * $menuItem->pivot->quantity;
                });
            });

            return $basePrice + $modifiersPrice;
        });
    }
}
