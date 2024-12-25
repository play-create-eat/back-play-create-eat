<?php

namespace App\Enums\Invitation;

enum Status: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case EXPIRED = 'expired';

    public static function values(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }
}
