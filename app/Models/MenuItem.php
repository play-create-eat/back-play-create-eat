<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MenuItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'menu_category_id',
        'menu_type_id',
        'name',
        'price',
        'description'
    ];

    protected $appends = ['cents_price'];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($theme) {
            $theme->clearMediaCollection('menu_item_images');
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('menu_item_images')->useDisk('s3');

    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MenuTag::class, 'menu_item_menu_tags');
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'menu_item_modifier_group');
    }

    public function celebrations(): BelongsToMany
    {
        return $this->belongsToMany(Celebration::class, 'celebration_menu_items')->withPivot('quantity');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(MenuType::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(MenuItemOption::class);
    }

    public function getCentsPriceAttribute(): float|int
    {
        return $this->price * 100;
    }
}
