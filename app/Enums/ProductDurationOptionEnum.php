<?php

namespace App\Enums;

/**
 * Duration value use CarbonInterval::createFromDateString
 */
enum ProductDurationOptionEnum: string
{
    case ONE_HOUR = '1 hour';
    case TWO_HOURS = '2 hours';
    case ONE_DAY = '1 day';
    case FIVE_DAYS = '5 days';
    case TEN_DAYS = '10 days';
    case TWENTY_DAYS = '20 days';

    public static function values(): array
    {
        return array_map(fn($type) => $type->value, self::cases());
    }
}
