<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MenuItemOption extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['menu_item_id', 'description'];

    protected $appends = ['image'];

    protected $hidden = ['media'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function getImageAttribute(): string
    {
        return $this->getFirstMediaUrl('menu_item_option_image');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('menu_item_option_image')->useDisk('s3');
    }
}
