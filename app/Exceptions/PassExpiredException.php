<?php

namespace App\Exceptions;

use App\Models\Pass;
use Exception;

class PassExpiredException extends Exception
{
    protected Pass $pass;

    public function __construct(Pass $pass)
    {
        $this->pass = $pass;

        parent::__construct("The pass with {$pass->serial} has expired on " . $pass->expired_at->toDateTimeString() . ".");
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }
}
