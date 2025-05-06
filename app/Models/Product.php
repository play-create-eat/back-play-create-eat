<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Customer;
use Bavix\Wallet\Interfaces\ProductLimitedInterface;

/**
 * @property int $id
 * @property string $name
 * @property ?string $description
 * @property ?ProductType[] $features
 * @property int $duration_time
 * @property int $price
 * @property ?int $price_weekend
 * @property double $discount_percent
 * @property double $cashback_percent
 * @property double $fee_percent
 * @property bool $is_extendable
 * @property bool $is_available
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 */
class Product extends Model implements ProductLimitedInterface
{
    use HasWallet, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'duration_time',
        'price',
        'price_weekend',
        'discount_percent',
        'cashback_percent',
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
        return $this->getFinalPrice(Carbon::now());
    }

    public function getMetaProduct(): ?array
    {
        return [
            'title'             => $this->name,
            'description'       => $this->description,
            'price'             => $this->price,
            'price_weekend'     => $this->price_weekend,
            'discount_percent'  => $this->fee_percent,
            'cashback_percent'  => $this->cashback_percent,
            'fee_percent'       => $this->fee_percent,
            'features'          => $this->features()->pluck('name', 'id')->toArray(),
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(ProductType::class, 'product_features', 'product_id', 'product_type_id');
    }

    /**
     * Scope a query to only include available products.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_available', true);
    }

    public function getFinalPrice(Carbon $date = null): int
    {
        $discount = max(0, min($this->discount_percent, 100));
        $fee = max(0, min($this->fee_percent, 100));

        $price = $this->price;

        if ($date?->isWeekend() && $this->price_weekend > 0) {
            $price = $this->price_weekend;
        }

        if ($discount > 0) {
            $price -= round($price * ($discount / 100));
        }

        if ($fee > 0) {
            $price += round($price * ($fee / 100));
        }

        return max(0, $price);
    }

    protected function casts(): array
    {
        return [
            'duration_time'     => 'integer',
            'price'             => 'integer',
            'price_weekend'     => 'integer',
            'discount_percent'  => 'decimal:2',
            'cashback_percent'  => 'decimal:2',
            'fee_percent'       => 'decimal:2',
            'is_extendable'     => 'boolean',
            'is_available'      => 'boolean',
        ];
    }
}
