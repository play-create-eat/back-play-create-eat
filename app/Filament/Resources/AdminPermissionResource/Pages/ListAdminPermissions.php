<?php

namespace App\Filament\Resources\AdminPermissionResource\Pages;

use App\Filament\Resources\AdminPermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminPermissions extends ListRecords
{
    protected static string $resource = AdminPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
