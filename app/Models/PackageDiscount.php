<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PackageDiscount extends Model
{
    protected $fillable = [
        'package_id',
        'name',
        'discount_percentage',
        'discount_amount',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isValidForDate(Carbon $date): bool
    {
        return $this->is_active
            && $date->between($this->start_date, $this->end_date);
    }

    public function calculateDiscountedPrice(float $originalPrice): float
    {
        if ($this->discount_amount) {
            return max(0, $originalPrice - $this->discount_amount);
        }

        if ($this->discount_percentage) {
            $discountAmount = ($originalPrice * $this->discount_percentage) / 100;
            return max(0, $originalPrice - $discountAmount);
        }

        return $originalPrice;
    }
}
