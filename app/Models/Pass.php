<?php

namespace App\Models;

use Bavix\Wallet\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $serial
 * @property int $remaining_time
 * @property bool $is_extendable
 * @property ?User $user
 * @property ?Child $children
 * @property ?\Bavix\Wallet\Models\Transfer $transfer
 * @property ?\Carbon\Carbon $entered_at
 * @property ?\Carbon\Carbon $exited_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 */
class Pass extends Model
{
    use SoftDeletes;

    protected $fillable = ['serial', 'remaining_time', 'is_extendable', 'entered_at', 'exited_at', 'expires_at'];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'remaining_time' => 'integer',
            'is_extendable' => 'bool',
            'entered_at'  => 'datetime',
            'exited_at'  => 'datetime',
            'expires_at' => 'datetime',
        ];
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

    public function isExpired(): bool
    {
        return $this->expires_at->lte(Carbon::now());
    }
}
