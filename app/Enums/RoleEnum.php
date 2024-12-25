<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super_admin';
    case PLATFORM_ADMIN = 'platform_admin';
    case PARENT = 'parent';
    case CHILD = 'child';

    public static function values(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }
}
