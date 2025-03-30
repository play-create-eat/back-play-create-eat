<?php

namespace App\Filament\Resources\InvitationTemplateResource\Pages;

use App\Filament\Resources\InvitationTemplateResource;
use App\Jobs\GenerateInvitationPreview;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvitationTemplate extends EditRecord
{
    protected static string $resource = InvitationTemplateResource::class;

    protected function afterSave(): void
    {
        GenerateInvitationPreview::dispatch($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
