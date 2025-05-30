<?php

namespace App\Exceptions;

use App\Models\ProductPackage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductPackageNotAvailableException extends HttpException
{
    protected ProductPackage $productPackage;

    public function __construct(ProductPackage $productPackage, string $message = '', int $statusCode = 403)
    {
        $this->productPackage = $productPackage;

        if (empty($message)) {
            $message = "Product package with ID {$productPackage->id} is not available.";
        }

        parent::__construct($statusCode, $message);
    }

    public function getProductPackage(): ProductPackage
    {
        return $this->productPackage;
    }
}
