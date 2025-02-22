<?php

namespace App\Enums;

use ArchTech\Enums\Values;
use ArchTech\Enums\Options;

/**
 * Duration value use DateInterval::createFromDateString to create a DateInterval
 */
enum ProductDurationOptionEnum: string
{
    use Values, Options;

    case ONE_HOUR = '1 hour';
    case TWO_HOURS = '2 hours';
    case ONE_DAY = '1 day';
    case FIVE_DAYS = '5 days';
    case TEN_DAYS = '10 days';
    case TWENTY_DAYS = '20 days';
}
