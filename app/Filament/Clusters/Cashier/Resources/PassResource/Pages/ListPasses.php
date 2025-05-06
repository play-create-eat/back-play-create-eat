<?php

namespace App\Filament\Clusters\Cashier\Resources\PassResource\Pages;

use App\Filament\Clusters\Cashier\Resources\PassResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPasses extends ListRecords
{
    protected static string $resource = PassResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'available' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->available()),
            'expired' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->expired()),
            'playground' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->active()),
        ];
    }
}
