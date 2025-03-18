<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Theme extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'type',
        'category',
    ];
    protected $hidden = ['media'];
    protected $appends = ['image'];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($theme) {
            $theme->clearMediaCollection('main_images');
            $theme->clearMediaCollection('theme_images');
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_images')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('s3');

        $this->addMediaCollection('theme_images')
            ->useDisk('s3');
    }

    public function getImageAttribute(): string
    {
        return $this->getFirstMediaUrl('theme_images');
    }

    public function getMainImageUrlAttribute(): ?string
    {
        return $this->getMedia('menu_images')
            ->where('custom_properties.main', true)
            ->first()
            ?->getUrl();
    }
}
