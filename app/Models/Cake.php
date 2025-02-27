<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cake extends Model
{
    protected $fillable = [
        'type',
        'recommended_weight',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cake_images')->useDisk('public');
    }
}
