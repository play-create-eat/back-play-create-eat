<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ModifierOption extends Model
{
    protected $fillable = ['modifier_group_id', 'name', 'price', 'nutrition_info'];


    protected $casts = [
        'nutrition_info' => 'array',
    ];

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }
}
