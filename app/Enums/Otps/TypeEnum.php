<?php

namespace App\Enums\Otps;

enum TypeEnum: string
{
    case EMAIL = 'email';

    case PHONE = 'phone';

    public static function values(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }
}
