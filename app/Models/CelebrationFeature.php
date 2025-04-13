<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::creating(function ($celebrationFeature) {
            $celebrationFeature->slug = Str::slug($celebrationFeature->title);
        });

        static::updating(function ($celebrationFeature) {
            $celebrationFeature->slug = Str::slug($celebrationFeature->title);
        });
    }

    public function celebration(): BelongsToMany
    {
        return $this->belongsToMany(Celebration::class)->withTimestamps();
    }
}
