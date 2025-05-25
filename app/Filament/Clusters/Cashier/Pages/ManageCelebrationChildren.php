<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Resources\CelebrationResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use App\Filament\Clusters\Cashier\Resources\CelebrationResource\RelationManagers\CelebrationChildrenRelationManager;
use Filament\Actions;

class ManageCelebrationChildren extends ManageRelatedRecords
{
    protected static string $resource = CelebrationResource::class;

    protected static string $relationship = 'invitations';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $title = 'Manage Celebration Children';

    protected static ?string $cluster = Cashier::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Celebration')
                ->url(fn () => CelebrationResource::getUrl('edit', ['record' => $this->getRecord()]))
                ->color('secondary')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            CelebrationChildrenRelationManager::make(),
        ];
    }
}
