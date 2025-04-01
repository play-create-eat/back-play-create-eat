<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ModifierGroup extends Model
{
    protected $fillable = ['menu_item_id', 'title', 'min_amount', 'max_amount', 'required'];

    public function menuItem(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_modifier_group');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ModifierOption::class);
    }
}
