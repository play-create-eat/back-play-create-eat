<?php

namespace App\Filament\Resources\PassResource\Pages;

use App\Filament\Resources\PassResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = PassResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
