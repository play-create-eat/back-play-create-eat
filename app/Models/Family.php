<?php

namespace App\Models;

use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Traits\HasWallets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Family extends Model
{
    use HasWallets;

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
}
