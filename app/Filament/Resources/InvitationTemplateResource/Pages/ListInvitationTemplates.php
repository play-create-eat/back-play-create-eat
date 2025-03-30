<?php

namespace App\Filament\Resources\InvitationTemplateResource\Pages;

use App\Filament\Resources\InvitationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvitationTemplates extends ListRecords
{
    protected static string $resource = InvitationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
