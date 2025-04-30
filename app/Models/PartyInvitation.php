<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PartyInvitation extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'party_invitation_template_id',
        'celebration_id',
    ];

    protected $appends = ['image_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('party_invitations')
            ->useDisk('s3');
    }

    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PartyInvitationTemplate::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('party_invitations');
    }

}
