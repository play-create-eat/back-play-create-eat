<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'celebration_id',
        'package_id',
        'child_name',
        'children_count',
        'start_time',
        'end_time',
        'setup_start_time',
        'cleanup_end_time',
        'special_requests',
        'status',
    ];

    protected $casts = [
        'start_time'       => 'datetime',
        'end_time'         => 'datetime',
        'setup_start_time' => 'datetime',
        'cleanup_end_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tables(): BelongsToMany
    {
        return $this->belongsToMany(Table::class);
    }

    public function getRequiredTableCount(): int
    {
        return ceil($this->children_count / 15);
    }
}
