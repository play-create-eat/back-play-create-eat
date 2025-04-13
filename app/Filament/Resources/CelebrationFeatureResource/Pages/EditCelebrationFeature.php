<?php

namespace App\Filament\Resources\CelebrationFeatureResource\Pages;

use App\Filament\Resources\CelebrationFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCelebrationFeature extends EditRecord
{
    protected static string $resource = CelebrationFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
