<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageFeature extends Model
{
    protected $fillable = [
        'package_id',
        'name',
        'status',
        'order',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
