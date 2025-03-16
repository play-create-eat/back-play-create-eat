<?php

namespace App\Exceptions;

use InvalidArgumentException;

class InvalidExtendableTimeException extends InvalidArgumentException
{
    public function __construct($extendableTime)
    {
        parent::__construct("Extendable time must be greater than 0. Given: {$extendableTime}");
    }
}
