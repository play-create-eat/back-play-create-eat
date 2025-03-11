<?php

namespace App\Filament\Resources\ModifierOptionResource\Pages;

use App\Filament\Resources\ModifierOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModifierOptions extends ListRecords
{
    protected static string $resource = ModifierOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
