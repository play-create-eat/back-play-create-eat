<?php

namespace App\Filament\Resources\CakeResource\Pages;

use App\Filament\Resources\CakeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCakes extends ListRecords
{
    protected static string $resource = CakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
