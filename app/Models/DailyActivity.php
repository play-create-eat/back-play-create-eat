<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DailyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'location',
        'category',
        'color',
        'is_active',
        'days_of_week',
        'order',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
        'days_of_week' => 'array',
    ];

    protected $appends = [
        'formatted_time',
        'duration_minutes',
    ];

    public function getFormattedTimeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' . Carbon::parse($this->end_time)->format('H:i');
    }

    public function getDurationMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $end->diffInMinutes($start);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where(function ($q) use ($dayOfWeek) {
            $q->whereJsonContains('days_of_week', $dayOfWeek)
              ->orWhereNull('days_of_week');
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('start_time');
    }

    public function scopeByTimeRange($query, $startTime = null, $endTime = null)
    {
        if ($startTime) {
            $query->where('start_time', '>=', $startTime);
        }
        if ($endTime) {
            $query->where('end_time', '<=', $endTime);
        }
        return $query;
    }
}
