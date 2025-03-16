<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CelebrationMenu extends Model
{
    protected $fillable = ['celebration_id', 'menu_item_id', 'quantity'];

    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(CelebrationMenuModifier::class);
    }
}
