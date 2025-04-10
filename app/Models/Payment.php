<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'family_id',
        'amount',
        'status',
        'payable_id',
        'payable_type',
        'deleted_at'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'amount'     => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
