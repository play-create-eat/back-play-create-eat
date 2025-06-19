<?php

namespace App\Data\Products;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class PassPurchaseProductData extends Data
{
    public function __construct(
        #[MapInputName('child_id')]
        public int     $childId,
        #[MapInputName('product_id')]
        public int     $productId,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        public ?Carbon $date
    )
    {
    }
}
