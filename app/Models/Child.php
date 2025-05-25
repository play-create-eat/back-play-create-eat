<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property GenderEnum $gender
 * @property Carbon $birth_date
 * @property ?Family $family
 * @property Carbon $created_at
 * @property Carbon $updated_at
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

    protected $appends = [
        'full_name',
        'custom_name'
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

    public function celebrations(): HasMany
    {
        return $this->hasMany(Celebration::class);
    }

    public function parties()
    {
        return $this->belongsToMany(Celebration::class, 'celebration_child')
            ->withTimestamps();
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => "$this->first_name $this->last_name",
        );
    }

    public function customName(): Attribute
    {
        return Attribute::make(
            get: fn() => "$this->first_name $this->last_name - {$this->family->name}",
        );
    }

}
