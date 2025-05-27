<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewCelebration extends ViewRecord
{
    protected static string $resource = CelebrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
            ];
    }

}
