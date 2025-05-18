<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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
        return $this->hasMany(PackageFeature::class);
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

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class);
    }
}
