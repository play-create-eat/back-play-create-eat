<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = ['name', 'capacity', 'status'];

    public function bookings(): HasMany
    {
        return $this->hasMany(TableBooking::class);
    }
}
