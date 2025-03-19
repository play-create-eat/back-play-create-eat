<?php

namespace App\Exceptions;

use App\Models\Pass;
use Carbon\CarbonInterval;
use Exception;

class PassRemainingTimeExceededException extends Exception
{
    protected Pass $pass;

    public function __construct(Pass $pass, string $message = '', int $code = 0)
    {
        $this->pass = $pass;

        if (empty($message)) {
            $readableRemaining = CarbonInterval::minute($pass->remaining_time)->forHumans();
            $message = "The pass {$pass->serial} has exceeded its allowed remaining time. Allowed remaining time: {$readableRemaining}.";
        }

        parent::__construct($message, $code);
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }
}
