<?php

namespace App\Http\Resources\Api\v1;

use Carbon\Carbon;
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
        if ($date = $request->input('date')) {
          $date = Carbon::parse($date);
        }

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'price'         => $this->getPriceByDate($date),
            'price_base'    => $this->price,
            'price_weekend' => $this->price_weekend,
            'fee_percent'   => $this->fee_percent,
            'is_extendable' => $this->is_extendable,
            'features'      => ProductFeatureResource::collection($this->whenLoaded('features')),
        ];
    }
}
