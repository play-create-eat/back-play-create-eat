<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

class AdminResourceBase extends Resource
{
    /**
     * Define common functionality for admin resources
     */
    public static function getNavigationGroup(): ?string
    {
        return 'Admin Management';
    }
}
