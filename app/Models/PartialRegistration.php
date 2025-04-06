<?php

namespace App\Models;

use App\Enums\IdTypeEnum;
use App\Enums\PartialRegistrationStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Model;

class PartialRegistration extends Model
{
    use HasUuids;
    protected $fillable = [
        'first_name',
        'last_name',
        'id_type',
        'id_number',
        'email',
        'phone_number',
        'password',
        'status',
        'family_id',
        'document_signed'
    ];

    protected $casts = [
        'id_type' => IdTypeEnum::class,
        'status'  => PartialRegistrationStatusEnum::class
    ];
}
