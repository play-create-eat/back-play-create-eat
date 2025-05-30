<?php

namespace App\Filament\Resources\ProductPackageResource\Pages;

use App\Filament\Resources\ProductPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductPackage extends EditRecord
{
    protected static string $resource = ProductPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
