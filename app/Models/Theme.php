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
        'age',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('theme_images')->useDisk('public');
    }
}
