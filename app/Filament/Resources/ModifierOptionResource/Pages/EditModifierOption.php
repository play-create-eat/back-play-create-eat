<?php

namespace App\Filament\Resources\ModifierOptionResource\Pages;

use App\Filament\Resources\ModifierOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModifierOption extends EditRecord
{
    protected static string $resource = ModifierOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
