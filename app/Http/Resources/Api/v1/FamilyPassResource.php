<?php

namespace App\Http\Resources\Api\v1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyPassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial' => $this->serial,
            'remaining_time' => (int)$this->remaining_time,
            'is_extendable' => (bool)$this->is_extendable,
            'is_expired' => $this->isExpired(),

            'children' => new FamilyPassChildrenResource($this->whenLoaded('children')),
            ...$this->whenLoaded('transfer', function ($transfer) {
                $features = collect($transfer->deposit ? $transfer->deposit->meta["features"] : [])
                    ->map(fn(string $name, int $id) => ['id' => $id, 'name' => $name])->values();

                $meta = $transfer->deposit ? [
                    'product' => collect($transfer->deposit->meta)->only(['title', 'description']),
                    'amount' => (int)$transfer->deposit->amount,
                ] : [];

                return [
                    ...$meta,
                    'discount' => (int)$transfer->discount,
                    'status' => $transfer->status,
                    'features' => $features,
                ];
            }, []),
            'activation_date' => Carbon::parse($this->activation_date)->toDateString(),
            'expires_at' => Carbon::parse($this->expires_at)->toIso8601String(),
            'created_at' => Carbon::parse($this->created_at)->toIso8601String()
        ];
    }
}
