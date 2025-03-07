<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableBooking extends Model
{
    protected $fillable = ['celebration_id', 'table_id'];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function celebration(): BelongsTo
    {
        return $this->belongsTo(Celebration::class);
    }
}
