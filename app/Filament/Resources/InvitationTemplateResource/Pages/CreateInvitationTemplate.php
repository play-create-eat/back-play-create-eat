<?php

namespace App\Filament\Resources\InvitationTemplateResource\Pages;

use App\Filament\Resources\InvitationTemplateResource;
use App\Jobs\GenerateInvitationPreview;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitationTemplate extends CreateRecord
{
    protected static string $resource = InvitationTemplateResource::class;

    protected function afterCreate(): void
    {
        GenerateInvitationPreview::dispatch($this->record);
    }
}
