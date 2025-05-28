<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'play_interesting',
        'play_safe',
        'play_staff_friendly',
        'create_activities_interesting',
        'create_staff_friendly',
        'eat_liked_food',
        'eat_liked_drinks',
        'eat_liked_pastry',
        'eat_team_friendly',
        'conclusion_suggestions',
        'user_email',
        'ip_address',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'play_interesting' => 'boolean',
            'play_safe' => 'boolean',
            'play_staff_friendly' => 'boolean',
            'create_activities_interesting' => 'boolean',
            'create_staff_friendly' => 'boolean',
        ];
    }
} 