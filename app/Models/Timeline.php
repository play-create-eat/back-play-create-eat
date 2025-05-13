<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function packages(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
