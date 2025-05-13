<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCelebration extends EditRecord
{
    protected static string $resource = CelebrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
