<?php

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'price'         => $this->price,
            'price_weekend' => $this->price_weekend,
            'fee_percent'   => $this->fee_percent,
            'is_extendable' => $this->is_extendable,
            'features'      => ProductFeatureResource::collection($this->whenLoaded('features')),
        ];
    }
}
