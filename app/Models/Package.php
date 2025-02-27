<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'description',
        'weekday_price',
        'weekend_price',
        'min_children',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(PackageFeature::class);
    }
}
