<?php

namespace App\Filament\Resources\PartyInvitationTemplateResource\Pages;

use App\Filament\Resources\PartyInvitationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartyInvitationTemplates extends ListRecords
{
    protected static string $resource = PartyInvitationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
