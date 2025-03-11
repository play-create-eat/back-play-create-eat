<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Child extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'family_id'
    ];

    protected $casts = [
        'gender'     => GenderEnum::class,
        'birth_date' => 'date'
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('child_avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('s3');
    }
}
