<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PartyInvitationTemplate extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'text_color',
    ];

    protected $appends = ['image_url', 'preview_url'];

    protected $hidden = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('party_invitation_templates')
            ->singleFile()
            ->useDisk('s3');

        $this->addMediaCollection('party_invitation_previews')
            ->singleFile()
            ->useDisk('s3');
    }

    public function getImageUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('party_invitation_templates');
    }

    public function getPreviewUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('party_invitation_previews');
    }

    public function partyInvitations(): HasMany
    {
        return $this->hasMany(PartyInvitation::class);
    }
}
