<?php

namespace App\Models;

use Bavix\Wallet\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property ?int $pass_package_id
 * @property string $serial
 * @property int $remaining_time
 * @property bool $is_extendable
 * @property ?User $user
 * @property ?Child $children
 * @property ?\Bavix\Wallet\Models\Transfer $transfer
 * @property ?PassPackage $passPackage
 * @property ?\Carbon\Carbon $entered_at
 * @property ?\Carbon\Carbon $exited_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $activation_date
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
            'entered_at' => 'datetime',
            'exited_at' => 'datetime',
            'expires_at' => 'datetime',
            'activation_date' => 'date',
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

    public function passPackage(): BelongsTo
    {
        return $this->belongsTo(PassPackage::class);
    }

    public function scopeAvailable($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where(function ($q) {
                $q->whereNotNull('pass_package_id')
                    ->where('expires_at', '>=', now());
            })
            ->orWhere(function ($q) {
                $q->whereNull('pass_package_id')
                    ->where('expires_at', '>=', now())
                    ->where('remaining_time', '>', 0);
            });
    }

    public function scopeExpired($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where(function ($q) {
                $q->whereNotNull('pass_package_id')
                    ->where('expires_at', '<', now());
            })
            ->orWhere(function ($q) {
                $q->whereNull('pass_package_id')
                    ->where(function ($q) {
                        $q->where('expires_at', '<', now())
                            ->orWhere('remaining_time', '<=', 0);
                    });
            });
    }

    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->whereNotNull('entered_at')
            ->whereDate('entered_at', Carbon::today())
            ->where(fn($q) => $q->whereNull('exited_at')
                ->orWhereColumn('entered_at', '>', 'exited_at')
            );
    }

    public function isExpired(): bool
    {
        if ($this->pass_package_id) {
            return $this->expires_at->lte(Carbon::now());
        }
        return $this->expires_at->lte(Carbon::now()) || $this->remaining_time <= 0;
    }

    public function isUnused(): bool
    {
        return $this->entered_at === null && $this->exited_at === null;
    }
}
