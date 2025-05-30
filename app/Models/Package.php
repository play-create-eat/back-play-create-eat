<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Carbon\Carbon;

class Package extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'weekday_price',
        'weekend_price',
        'min_children',
        'duration_hours',
        'cashback_percentage',
        'bonus_playground_visit',
        'order'
    ];

    protected $hidden = ['media'];

    protected $appends = ['images'];

    protected $casts = [
        'weekday_price'          => 'decimal:2',
        'weekend_price'          => 'decimal:2',
        'cashback_percentage'    => 'decimal:2',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(PackageFeature::class)->orderBy('order');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(PackageDiscount::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class)->orderBy('order');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('package_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('s3');
    }

    public function getImagesAttribute()
    {
        return $this->getMedia('package_images')->map(function ($media) {
            return $media->getUrl();
        });
    }

    /**
     * Get the final price for a specific date (with discount applied if available)
     */
    public function getPriceForDate(Carbon $date): float
    {
        $basePrice = $date->isBusinessWeekend()
            ? (float) $this->attributes['weekend_price']
            : (float) $this->attributes['weekday_price'];

        $activeDiscount = $this->getActiveDiscountForDate($date);

        if ($activeDiscount) {
            return $activeDiscount->calculateDiscountedPrice($basePrice);
        }

        return $basePrice;
    }

    public function getActiveDiscountForDate(Carbon $date): ?PackageDiscount
    {
        return $this->discounts()
            ->where('is_active', true)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->orderBy('discount_percentage', 'desc')
            ->orderBy('discount_amount', 'desc')
            ->first();
    }

    public function getWeekendPriceAttribute(): float
    {
        $today = Carbon::today();
        if ($today->isBusinessWeekend()) {
            return $this->getPriceForDate($today);
        }

        return (float) $this->attributes['weekend_price'];
    }


    public function getWeekdayPriceAttribute(): float
    {
        $today = Carbon::today();
        if (!$today->isBusinessWeekend()) {
            return $this->getPriceForDate($today);
        }

        return (float) $this->attributes['weekday_price'];
    }


    /**
     * Get raw weekend price without any discounts
     */
    public function getRawWeekendPrice(): float
    {
        return (float) $this->attributes['weekend_price'];
    }

    /**
     * Get raw weekday price without any discounts
     */
    public function getRawWeekdayPrice(): float
    {
        return (float) $this->attributes['weekday_price'];
    }

}
