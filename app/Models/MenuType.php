<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MenuType extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['title', 'description'];

    protected $appends = ['images'];

    protected $hidden = ['media'];

    public function categories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('menu_type_images')
            ->useDisk('s3');
    }

    public function getImagesAttribute()
    {
        return $this->getMedia('menu_type_images')->map(function ($media) {
            return $media->getUrl();
        });
    }
}
