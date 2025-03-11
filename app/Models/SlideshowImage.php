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

    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('slideshow_images')->useDisk('public');
    }
}
