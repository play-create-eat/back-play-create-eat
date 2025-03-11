<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
       'type',
       'name',
       'category',
    ];

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }
}
