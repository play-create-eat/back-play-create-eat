<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCelebrations extends ListRecords
{
    protected static string $resource = CelebrationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
