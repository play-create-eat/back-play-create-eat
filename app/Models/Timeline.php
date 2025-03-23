<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    protected $fillable = [
        'title',
        'duration',
        'is_premium',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
    ];
}
