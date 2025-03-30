<?php

namespace App\Exceptions;

use App\Models\Pass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidPassActivationDateException extends HttpException
{
    protected Pass $pass;

    public function __construct(Pass $pass, string $message = null, int $statusCode = 403)
    {
        $this->pass = $pass;

        if (empty($message)) {
            $message = "Ticket pass is not valid for scanning today. Activation date: {$pass->activation_date->toDateString()}";
        }

        parent::__construct($statusCode, $message);

    }
}
