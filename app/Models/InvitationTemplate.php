<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InvitationTemplate extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['logo_color', 'background_color', 'text_color', 'decoration_type'];

    protected $appends = ['preview_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invitation_preview')->useDisk('s3');
    }

    public function getPreviewUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('invitation_preview');
    }
}
