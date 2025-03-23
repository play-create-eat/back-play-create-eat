<?php

namespace App\Exceptions;

use App\Models\Pass;
use App\Models\ProductType;
use Exception;

class PassFeatureNotAvailableException extends Exception
{
    protected Pass $pass;
    protected ProductType $productType;

    public function __construct(Pass $pass, ProductType $productType, string $message = '', int $code = 0)
    {
        $this->pass = $pass;

        if (empty($message)) {
            $message = "The pass with {$pass->serial} doesn't support feature {$productType->name}.";
        }

        parent::__construct($message, $code);
    }
}
