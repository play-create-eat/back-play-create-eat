<?php

namespace App\Filament\Resources\PackageDiscountResource\Pages;

use App\Filament\Resources\PackageDiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackageDiscounts extends ListRecords
{
    protected static string $resource = PackageDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
