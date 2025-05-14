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
            Actions\Action::make('manageInvitedChildren')
                ->label('Manage Invited Children')
                ->icon('heroicon-o-user-group')
                ->url(fn () => $this->getResource()::getUrl('manage-invited-children', ['record' => $this->record])),

        ];
    }
}
