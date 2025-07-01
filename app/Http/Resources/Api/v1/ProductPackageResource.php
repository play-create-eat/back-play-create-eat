<?php

namespace App\Http\Resources\Api\v1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProductPackage
 */
class ProductPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($date = $request->input('date')) {
          $date = Carbon::parse($date);
        }

        return [
            'id'                        => $this->id,
            'name'                      => $this->name,
            'description'               => $this->description,
            'price'                     => $this->getFinalPrice($date),
            'price_base'                => $this->price,
            'price_discount'            => $this->discount_price,
            'product_quantity'          => $this->product_quantity,
            'product'                   => new ProductPackageProductResource($this->whenLoaded('product')),
            'campaign'                  => [
                'active'        => $this->campaign_active,
                'start_date'    => $this->campaign_start_date,
                'end_date'      => $this->campaign_end_date,
            ],
        ];
    }
}
