<?php

namespace App\Filament\Resources\MenuTagResource\Pages;

use App\Filament\Resources\MenuTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenuTags extends ListRecords
{
    protected static string $resource = MenuTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
