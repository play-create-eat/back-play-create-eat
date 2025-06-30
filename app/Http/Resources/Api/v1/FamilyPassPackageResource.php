<?php

namespace App\Http\Resources\Api\v1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PassPackage
 */
class FamilyPassPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'quantity'          => $this->quantity,
            'is_refundable'     => false,
            'is_expired'        => $this->isExpired(),
            'product_package'   => new ProductPackageResource($this->whenLoaded('productPackage')),
            'children'          => new FamilyPassChildrenResource($this->whenLoaded('children')),
            'created_at'        => Carbon::parse($this->created_at)->toIso8601String()
        ];
    }
}
