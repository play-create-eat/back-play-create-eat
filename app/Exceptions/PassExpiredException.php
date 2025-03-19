<?php

namespace App\Exceptions;

use App\Models\Pass;
use Exception;

class PassExpiredException extends Exception
{
    protected Pass $pass;

    public function __construct(Pass $pass, string $message = '', int $code = 0)
    {
        $this->pass = $pass;

        if (empty($message)) {
            $message = "The pass with {$pass->serial} has expired on {$pass->expired_at->toDateTimeString()}.";
        }

        parent::__construct($message, $code);
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }
}
