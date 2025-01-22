<?php

namespace App\Enums\Otps;

enum StatusEnum: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case EXPIRED = 'expired';
    case FAILED = 'failed';

    public static function values(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }
}
