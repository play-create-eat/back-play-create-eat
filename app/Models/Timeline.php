<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timeline extends Model
{
    protected $fillable = [
        'title',
        'duration',
        'package_id',
        'order',
    ];

    public function packages(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
