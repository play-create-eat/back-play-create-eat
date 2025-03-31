<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Cake extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'type',
        'price_per_kg',
    ];
    protected $hidden = ['media'];
    protected $appends = ['image'];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($theme) {
            $theme->clearMediaCollection('cake_images');
        });
    }

    public function getImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl('cake_images') ?: null;
    }


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cake_images')->useDisk('s3');
    }

}
