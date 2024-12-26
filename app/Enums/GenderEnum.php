<?php

namespace App\Enums;

enum GenderEnum: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_map(fn($gender) => $gender->value, self::cases());
    }
}
