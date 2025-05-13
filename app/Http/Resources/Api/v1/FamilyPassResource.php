<?php

namespace App\Http\Resources\Api\v1;

use App\Services\PassService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyPassResource extends JsonResource
{
    private PassService $passService;

    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->passService = app(PassService::class);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'serial'            => $this->serial,
            'remaining_time'    => (int)$this->remaining_time,
            'is_extendable'     => (bool)$this->is_extendable,
            'is_expired'        => $this->isExpired(),
            'is_refundable'     => false,
            'children' => new FamilyPassChildrenResource($this->whenLoaded('children')),
            ...$this->whenLoaded('transfer', function ($transfer) {
                $features = collect($transfer->deposit ? $transfer->deposit->meta["features"] : [])
                    ->map(fn(string $name, int $id) => ['id' => $id, 'name' => $name])->values();

                $loyaltyPoints = (int)($transfer->deposit->meta['loyalty_points_used'] ?? 0);
                $discount = (float)($transfer->deposit->meta['discount_percent'] ?? .0);
                $cashbackPercent = (float)($transfer->deposit->meta['cashback_percent'] ?? .0);
                $cashbackAmount = (int)($transfer->deposit->meta['cashback_amount'] ?? .0);
                $fee = (float)($transfer->deposit->meta['discount_percent'] ?? .0);

                $meta = $transfer->deposit ? [
                    'product'   => collect($transfer->deposit->meta)->only(['title', 'description']),
                    'amount'    => (int)$transfer->deposit->amount,
                ] : [];

                return [
                    ...$meta,
                    'is_refundable'     => rescue(fn () => $this->passService->isRefundable($this->resource), false),
                    'discount_percent'  => $discount,
                    'cashback_percent'  => $cashbackPercent,
                    'cashback_amount'   => $cashbackAmount,
                    'fee_percent'       => $fee,
                    'loyalty_points'    => $loyaltyPoints,
                    'status'            => $transfer->status,
                    'features'          => $features,
                ];
            }, []),
            'activation_date'   => Carbon::parse($this->activation_date)->toDateString(),
            'expires_at'        => Carbon::parse($this->expires_at)->toIso8601String(),
            'created_at'        => Carbon::parse($this->created_at)->toIso8601String()
        ];
    }
}
