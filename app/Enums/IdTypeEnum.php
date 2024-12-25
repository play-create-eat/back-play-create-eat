<?php

namespace App\Enums;

enum IdTypeEnum: string
{
    case PASSPORT = 'passport';
    case EMIRATES = 'emirates';

    public static function values(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }
}
