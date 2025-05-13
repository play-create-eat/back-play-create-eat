<?php

namespace App\Filament\Resources\PartyInvitationTemplateResource\Pages;

use App\Filament\Resources\PartyInvitationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditPartyInvitationTemplate extends EditRecord
{
    protected static string $resource = PartyInvitationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
