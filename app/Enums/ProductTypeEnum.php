<?php

namespace App\Enums;

enum ProductTypeEnum: string
{
    case PLAYGROUND_PASS = 'playground_pass';

    public static function values(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }
}
