<?php

namespace App\Exceptions;

use Exception;

class PassNotExtendableException extends Exception
{
    public function __construct($message = "Pass is not extendable.", $code = 0)
    {
        parent::__construct($message, $code);
    }
}
