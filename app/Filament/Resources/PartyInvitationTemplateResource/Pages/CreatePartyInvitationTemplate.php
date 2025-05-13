<?php

namespace App\Filament\Resources\PartyInvitationTemplateResource\Pages;

use App\Filament\Resources\PartyInvitationTemplateResource;
use App\Models\PartyInvitationTemplate;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePartyInvitationTemplate extends CreateRecord
{
    protected static string $resource = PartyInvitationTemplateResource::class;
}
