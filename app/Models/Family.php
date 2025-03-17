<?php

namespace App\Models;

use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Family extends Model
{
    use HasWallet;

    protected $fillable = ['name'];

    protected $hidden = ['deleted_at'];

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
        return $this->wallet('main');
    }

    public function loyaltyWallet(): MorphOne
    {
        return $this->wallet('loyalty');
    }
}
