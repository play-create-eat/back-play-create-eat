<?php

namespace App\Data\Products;

use Carbon\Carbon;
use Illuminate\Support\Optional;
use Spatie\LaravelData\Data;

class PassInfoData extends Data
{
    public function __construct(
        public int           $product_id,
        public int           $price,
        public string        $pass_serial,
        public string        $child_id,
        public string        $child_name,
        public Carbon        $activation_date,
        public Carbon        $expires_at,
        public int           $remaining_time,
        public bool          $is_extendable,
        public int|Optional  $discount = 0,
        public int|Optional  $cashback = 0,
        public int|Optional  $discount_price_weekday = 0,
        public int|Optional  $discount_price_weekend = 0,
        public int|Optional  $discount_percent = 0,
        public int|Optional  $cashback_percent = 0,
        public int|Optional  $fee_percent = 0,
        public bool|Optional $gift = false,
    )
    {
    }
}
