<?php

namespace App\Models;

use App\Enums\InvitationStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'code',
        'family_id',
        'creator_id',
        'status',
        'expired_at'
    ];

    protected $casts = [
        'status' => InvitationStatusEnum::class,
        'expired_at' => 'datetime'
    ];
}
