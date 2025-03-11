<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MenuItemMenuTag extends Pivot
{
    protected $table = 'menu_item_menu_tags';
    protected $fillable = ['menu_item_id', 'menu_tag_id'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function menuTag(): BelongsTo
    {
        return $this->belongsTo(MenuTag::class);
    }
}
