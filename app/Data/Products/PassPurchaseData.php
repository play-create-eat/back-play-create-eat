<?php

namespace App\Data\Products;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class PassPurchaseData extends Data
{
    public function __construct(
        #[DataCollectionOf(PassPurchaseProductData::class)]
        public DataCollection $products,
        public int|Optional   $loyaltyPointsAmount = 0,
        public bool|Optional  $gift = false,
    )
    {
        if ($this->gift) {
            $this->loyaltyPointsAmount = 0;
        }
    }
}
