<?php

namespace App\Exceptions;

use App\Models\Pass;
use Carbon\CarbonInterval;
use Exception;

class PassRemainingTimeExceededException extends Exception
{
    protected Pass $pass;

    public function __construct(Pass $pass)
    {
        $this->pass = $pass;

        $readableRemaining = CarbonInterval::minute($pass->remaining_time)->forHumans();

        parent::__construct("The pass {$pass->serial} has exceeded its allowed remaining time. Allowed remaining time: {$readableRemaining}.");
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }
}
