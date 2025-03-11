<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends Model
{
    protected $fillable = ['title'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
