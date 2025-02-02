<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'code',
        'phone_number',
        'role',
        'permissions',
        'created_by',
        'expires_at',
        'used',
    ];

    protected function casts(): array
    {
        return [
            'role'        => 'string',
            'permissions' => 'array',
            'expires_at'  => 'datetime',
        ];
    }
}
