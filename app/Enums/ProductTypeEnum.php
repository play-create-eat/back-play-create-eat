<?php

namespace App\Enums;

enum ProductTypeEnum: string
{
    case BASIC = 'basic';
    case PACKAGE = 'package';

    public static function values(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'Basic',
            self::PACKAGE => 'Package',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BASIC => 'gray',
            self::PACKAGE => 'info',
        };
    }
}
