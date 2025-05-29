<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use App\Models\Celebration;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Throwable;

class ViewCelebration extends ViewRecord
{
    protected static string $resource = CelebrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn($record): bool => auth()->guard('admin')->user()->can('updateCelebrations')),

            Action::make('printBill')
                ->label('Print Bill')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn($record) => URL::signedRoute(
                    'celebration.print-bill',
                    [
                        'celebration' => $record,
                        'adminId'     => auth()->guard('admin')->id()
                    ]
                ), true)
                ->openUrlInNewTab(),

            Action::make('closeCelebration')
                ->label('Close Celebration')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Close Celebration')
                ->modalDescription('This will finalize the celebration, update status and mark it as completed.')
                ->modalSubmitActionLabel('Close Celebration')
                ->action(fn() => $this->processCelebrationClosure())
                ->visible(fn($record): bool => is_null($record->closed_at)),

            Action::make('exitCelebration')
                ->label('Closed Celebration')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn($record): bool => !is_null($record->closed_at))
                ->disabled(),
        ];
    }

    /**
     * Process the celebration closure without recalculation
     *
     * @return void
     * @throws Throwable
     */
    protected function processCelebrationClosure(): void
    {
        try {
            DB::beginTransaction();

            /** @var Celebration $celebration */
            $celebration = $this->record;
            $celebration->children_count = $celebration->invitations()->count();
            $celebration->closed_at = now();
            $celebration->completed = true;
            $celebration->save();

            DB::commit();

            Notification::make()
                ->title('Celebration Closed Successfully')
                ->body("The celebration has been marked as completed.")
                ->success()
                ->send();

            $this->redirect(route('filament.admin.resources.celebrations.view', $celebration->id));
        } catch (Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->title('Failed to close celebration')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
