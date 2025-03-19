<?php

namespace App\Exceptions;

use Exception;
use App\Models\Product;

class ProductNotAvailableException extends Exception
{
    protected Product $product;

    public function __construct(Product $product, string $message = '', int $code = 0)
    {
        $this->product = $product;

        if (empty($message)) {
            $message = "Product with ID {$product->id} is not available.";
        }

        parent::__construct($message, $code);
    }

    public function getProduct(): Product
    {
        return $this->product;
    }
}
