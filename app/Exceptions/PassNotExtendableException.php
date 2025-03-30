<?php

namespace App\Exceptions;

use App\Models\Pass;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PassNotExtendableException extends HttpException
{
    protected Pass $pass;

    public function __construct(Pass $pass, string $message = '', int $statusCode = 403)
    {
        $this->pass = $pass;

        if (empty($message)) {
            $message = "Pass is not extendable.";
        }

        parent::__construct($statusCode, $message);
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }
}
