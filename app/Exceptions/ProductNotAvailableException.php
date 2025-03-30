<?php

namespace App\Exceptions;

use App\Models\Product;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductNotAvailableException extends HttpException
{
    protected Product $product;

    public function __construct(Product $product, string $message = '', int $statusCode = 403)
    {
        $this->product = $product;

        if (empty($message)) {
            $message = "Product with ID {$product->id} is not available.";
        }

        parent::__construct($statusCode, $message);
    }

    public function getProduct(): Product
    {
        return $this->product;
    }
}
