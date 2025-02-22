<?php

namespace App\Models;

use App\Enums\ProductTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Customer;
use Bavix\Wallet\Interfaces\ProductLimitedInterface;

/**
 * @property int $id
 * @property string $name
 * @property ?string $description
 * @property ProductTypeEnum $type
 * @property int $duration
 * @property int $price
 * @property ?int $price_weekend
 * @property double $fee_percent
 * @property bool $is_extendable
 * @property bool $is_available
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Product extends Model implements ProductLimitedInterface
{
    use HasWallet;

    protected $fillable = [
        'name',
        'description',
        'type',
        'duration',
        'price',
        'price_weekend',
        'fee_percent',
        'is_extendable',
        'is_available',
    ];

    public function canBuy(Customer $customer, int $quantity = 1, bool $force = false): bool
    {
        return true;
    }

    public function getAmountProduct(Customer $customer): int
    {
        $isWeekend = Carbon::now()->isWeekend();

        if ($isWeekend && $this->price_weekend) {
            return $this->price_weekend;
        }

        return $this->price;
    }

    public function getMetaProduct(): ?array
    {
        return [
            'title' => $this->name,
            'description' => 'Purchase of Product #' . $this->id,
        ];
    }

    protected function casts(): array
    {
        return [
            'type' => ProductTypeEnum::class,
            'duration' => 'integer',
            'price' => 'integer',
            'price_weekend' => 'integer',
            'fee_percent' => 'decimal:2',
            'is_extendable' => 'boolean',
            'is_available' => 'boolean',
        ];
    }
}
