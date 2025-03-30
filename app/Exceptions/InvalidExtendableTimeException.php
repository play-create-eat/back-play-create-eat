<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidExtendableTimeException extends HttpException
{
    public function __construct(int $extendableTime, string $message = '', int $statusCode = 406)
    {
        if (empty($message)) {
            $message = "Extendable time must be greater than 0. Given: {$extendableTime}";
        }

        parent::__construct($statusCode. $message);
    }
}
