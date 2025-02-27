<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Celebration extends Model
{
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
        'menu_id',
        'photo_album',
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
}
