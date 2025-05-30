<?php

namespace App\Exceptions;

use App\Models\ProductPackage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PassAlreadyExistsException extends HttpException
{
    public function __construct(string $message = '', int $statusCode = 403)
    {
        if (empty($message)) {
            $message = 'A pass already exists for the given date.';
        }

        parent::__construct($statusCode, $message);
    }
}
