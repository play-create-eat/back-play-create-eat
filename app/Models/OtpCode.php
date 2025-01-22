<?php

namespace App\Models;

use App\Enums\Otps\PurposeEnum;
use App\Enums\Otps\StatusEnum;
use App\Enums\Otps\TypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id',
        'identifier',
        'type',
        'code',
        'purpose',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'type'    => TypeEnum::class,
        'purpose' => PurposeEnum::class,
        'status'  => StatusEnum::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
