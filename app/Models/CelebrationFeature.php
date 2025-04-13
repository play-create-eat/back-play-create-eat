<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CelebrationFeature extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function celebration(): BelongsToMany
    {
        return $this->belongsToMany(Celebration::class);
    }
}
