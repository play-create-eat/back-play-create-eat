<?php

namespace App\Exceptions;

use Bavix\Wallet\Services\FormatterServiceInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InsufficientCashbackBalanceException extends HttpException
{
    protected int $amount;
    protected int $balance;

    public function __construct(int|string $amount, int|string $balance, string $message = '', int $statusCode = 402)
    {
        $this->amount = $amount;
        $this->balance = $balance;

        if (empty($message)) {
            $formater = app(FormatterServiceInterface::class);
            $message = "Insufficient cashback balance: required {$formater->floatValue($amount, 2)}, available {$formater->floatValue($balance, 2)}.";
        }

        parent::__construct($statusCode, $message);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }
}

