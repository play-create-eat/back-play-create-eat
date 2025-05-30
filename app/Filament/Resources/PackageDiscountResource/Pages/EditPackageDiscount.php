<?php

namespace App\Filament\Resources\PackageDiscountResource\Pages;

use App\Filament\Resources\PackageDiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackageDiscount extends EditRecord
{
    protected static string $resource = PackageDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
