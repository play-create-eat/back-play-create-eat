<?php

namespace App\Enums;

enum PartialRegistrationStatusEnum: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public static function values(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }
}
