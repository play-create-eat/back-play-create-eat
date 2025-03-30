<?php

namespace App\Exceptions;

use App\Models\Pass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PassExpiredException extends HttpException
{
    protected Pass $pass;

    public function __construct(Pass $pass, string $message = '', int $statusCode = 403)
    {
        $this->pass = $pass;

        if (empty($message)) {
            $message = "The pass with {$pass->serial} has expired on {$pass->expires_at->toDateTimeString()}.";
        }

        parent::__construct($statusCode, $message);
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }
}
