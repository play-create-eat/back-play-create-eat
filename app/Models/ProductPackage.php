<?php

namespace App\Models;

use Bavix\Wallet\Interfaces\Customer;
use Bavix\Wallet\Traits\HasWallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property ?string $description
 * @property int $price
 * @property ?int $discount_price
 * @property ?int $cashback_amount
 * @property Product $product
 * @property int $product_quantity
 * @property bool $is_available
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ?Carbon $deleted_at
 */
class ProductPackage extends Model
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
        'price',
        'discount_price',
        'cashback_amount',
        'product_id',
        'product_quantity',
        'is_available',
    ];

    public function canBuy(Customer $customer, int $quantity = 1, bool $force = false): bool
    {
        return true;
    }

    public function getAmountProduct(Customer $customer): int
    {
        return $this->getFinalPrice();
    }

    public function getMetaProduct(): ?array
    {
        return [
            'title' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'product_id' => $this->product->id,
            'product_quantity' => $this->product_quantity,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFinalPrice(): int
    {
        return $this->discount_price ?: $this->price;
    }

    /**
     * Scope a query to only include available products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAvailable($query): Builder
    {
        return $query->where('is_available', true);
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'discount_price' => 'integer',
            'cashback_amount' => 'integer',
            'product_quantity' => 'integer',
            'is_available' => 'boolean',
        ];
    }
}
