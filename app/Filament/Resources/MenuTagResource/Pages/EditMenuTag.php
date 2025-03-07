<?php

namespace App\Filament\Resources\MenuTagResource\Pages;

use App\Filament\Resources\MenuTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMenuTag extends EditRecord
{
    protected static string $resource = MenuTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
