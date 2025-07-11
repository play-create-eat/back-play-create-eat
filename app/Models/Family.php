<?php

namespace App\Models;

use Bavix\Wallet\Interfaces\Customer;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Traits\CanPay;
use Bavix\Wallet\Traits\HasWallets;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property ?User[] $users
 * @property ?Child[] $children
 * @property ?Wallet $main_wallet
 * @property ?Wallet $loyalty_wallet
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ?Carbon $deleted_at
 */
class Family extends Model implements Wallet, Customer
{
    use HasWallets, CanPay, SoftDeletes;

    protected $fillable = ['name', 'stripe_customer_id'];

    protected $hidden = ['deleted_at'];

    protected $casts = ['stripe_customer_id' => 'string'];

    protected static function booted(): void
    {
        static::retrieved(function ($family) {
            if (!$family->hasWallet('default')) {
                $family->createWallet([
                    'name' => 'Main Wallet',
                    'slug' => 'default',
                ]);
            }

            if (!$family->hasWallet('cashback')) {
                $family->createWallet([
                    'name' => 'Cashback Wallet',
                    'slug' => 'cashback',
                ]);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function getMainWalletAttribute(): ?Wallet
    {
        return $this->getWallet('default');
    }

    public function getLoyaltyWalletAttribute(): ?Wallet
    {
        return $this->getWallet('cashback');
    }

    public function passes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Pass::class,
            User::class,
            'family_id',
            'user_id',
            'id',
            'id',
        )->distinct();
    }

    public function passPackages(): HasManyThrough
    {
        return $this->hasManyThrough(
            PassPackage::class,
            User::class,
            'family_id',
            'user_id',
            'id',
            'id',
        )->distinct();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function celebrations(): HasMany
    {
        return $this->hasMany(Celebration::class);
    }
}
