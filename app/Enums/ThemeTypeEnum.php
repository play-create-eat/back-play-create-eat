<?php

namespace App\Enums;

enum ThemeTypeEnum: string
{
    case BIRTHDAY_PARTY = 'Birthday Party';

    public static function values(): array
    {
        return array_map(fn($gender) => $gender->value, self::cases());
    }
}
