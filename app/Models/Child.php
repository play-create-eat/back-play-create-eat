<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property \App\Enums\GenderEnum $gender
 * @property \Carbon\Carbon $birth_date
 * @property ?\App\Models\Family $family
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
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

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }
}
