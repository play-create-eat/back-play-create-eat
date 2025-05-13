<?php

namespace App\Exceptions;

use App\Models\Pass;
use App\Models\ProductType;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PassFeatureNotAvailableException extends HttpException
{
    protected Pass $pass;
    protected ProductType $productType;

    public function __construct(Pass $pass, ProductType $productType, string $message = '', int $statusCode = 403)
    {
        $this->pass = $pass;
        $this->productType = $productType;

        if (empty($message)) {
            $message = "The pass with {$pass->serial} doesn't support feature {$productType->name}.";
        }

        parent::__construct($statusCode, $message);
    }

    public function getPass(): Pass
    {
        return $this->pass;
    }

    public function getProductType(): ProductType
    {
        return $this->productType;
    }
}
