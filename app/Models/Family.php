<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;

class Family extends Model implements Wallet
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
}
