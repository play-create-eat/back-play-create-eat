<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SlideshowImage extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['celebration_id'];

    protected $appends = ['images'];

    protected $hidden = ['media'];

    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('slideshow_images')->useDisk('s3');
    }

    public function getImagesAttribute()
    {
        return $this->getMedia('slideshow_images')->map(function ($media) {
            return [
                'id'  => $media->id,
                'url' => $media->getUrl()
            ];
        });
    }
}
