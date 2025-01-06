<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Child extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'birth_date'
    ];

    protected $casts = [
        'gender'     => GenderEnum::class,
        'birth_date' => 'date'
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
