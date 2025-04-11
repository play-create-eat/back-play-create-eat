<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Table extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'is_preferred_for_children',
        'is_active',
    ];

    protected $casts = [
        'is_preferred_for_children' => 'boolean',
        'is_active'                 => 'boolean',
    ];

    /**
     * Check if the table is available for a specific time period.
     */
    public function isAvailable(string $setupStartTime, string $cleanupEndTime): bool
    {
        return !$this->bookings()
            ->where(function ($query) use ($setupStartTime, $cleanupEndTime) {
                $query->where(function ($q) use ($setupStartTime, $cleanupEndTime) {
                    $q->where('setup_start_time', '<', $cleanupEndTime)
                        ->where('cleanup_end_time', '>', $setupStartTime);
                });
            })
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }

    /**
     * The bookings that belong to the table.
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class);
    }
}
