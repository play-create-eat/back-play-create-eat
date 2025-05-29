<?php

namespace App\Filament\Resources\ProductPackageResource\Pages;

use App\Filament\Resources\ProductPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductPackage extends ViewRecord
{
    protected static string $resource = ProductPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
