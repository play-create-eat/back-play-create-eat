<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invite extends Model
{
    protected $fillable = [
        'child_name',
        'theme',
        'invite_image',
        'invite_pdf',
    ];

    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invite_images')->useDisk('public');
        $this->addMediaCollection('invite_pdfs')->useDisk('public');
    }
}
