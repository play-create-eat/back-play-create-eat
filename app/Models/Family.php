<?php

namespace App\Models;

use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Family extends Model
{
    use HasWallet;

    protected $fillable = ['name', 'stripe_customer_id'];

    protected $hidden = ['deleted_at'];

    protected $casts = ['stripe_customer_id' => 'string'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function mainWallet(): MorphOne
    {
        return $this->getWallet('main');
    }

    public function loyaltyWallet(): MorphOne
    {
        return $this->getWallet('loyalty');
    }
}
