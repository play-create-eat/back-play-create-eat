<?php

namespace App\Exceptions;

use App\Models\Pass;
use Exception;

class PassNotExtendableException extends Exception
{
    protected Pass $pass;

    public function __construct(Pass $pass, string $message = '', int $code = 0)
    {
        $this->pass;

        if (empty($message)) {
            $message = "Pass is not extendable.";
        }

        parent::__construct($message, $code);
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }
}
