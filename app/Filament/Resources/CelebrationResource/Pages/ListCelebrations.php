<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use Filament\Actions\Action;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCelebrations extends ListRecords
{
    protected static string $resource = CelebrationResource::class;

    public function getTabs(): array
    {
        return [
            'all'       => Tab::make('All'),
            'today'     => Tab::make('Today')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereToday('celebration_date')),
            'upcoming'  => Tab::make('Upcoming')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereTodayOrAfter('celebration_date')),
            'expired'   => Tab::make('Expired')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereBeforeToday('celebration_date')),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('completed', true)),
            'closed'    => Tab::make('Closed')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('closed_at')),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'today';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Celebration')
                ->url(CelebrationResource::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
