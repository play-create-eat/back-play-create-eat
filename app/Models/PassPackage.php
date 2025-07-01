<?php

namespace App\Models;

use Bavix\Wallet\Models\Transfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $quantity
 * @property ?ProductPackage $productPackage
 * @property ?Child $children
 * @property ?User $user
 * @property Pass[] $passes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ?\Bavix\Wallet\Models\Transfer $transfer
 * @property ?\Carbon\Carbon $deleted_at
 */
class PassPackage extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function productPackage(): BelongsTo
    {
        return $this->belongsTo(ProductPackage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function children(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id', 'id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function passes(): HasMany
    {
        return $this->hasMany(Pass::class);
    }

    public function scopeAvailable($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('quantity', '>', 0);
    }

    public function isExpired(): bool
    {
        return $this->quantity <= 0;
    }
}
