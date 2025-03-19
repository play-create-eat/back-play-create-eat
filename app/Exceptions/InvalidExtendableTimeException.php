<?php

namespace App\Exceptions;

use InvalidArgumentException;

class InvalidExtendableTimeException extends InvalidArgumentException
{
    public function __construct(int $extendableTime, string $message = '', int $code = 0)
    {
        if (empty($message)) {
            $message = "Extendable time must be greater than 0. Given: {$extendableTime}";
        }

        parent::__construct($message, $code);
    }
}
