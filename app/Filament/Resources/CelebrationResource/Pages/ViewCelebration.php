<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use App\Models\Celebration;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Carbon\Carbon;
use Exception;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewCelebration extends ViewRecord
{
    protected static string $resource = CelebrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn($record): bool => auth()->guard('admin')->user()->can('updateCelebrations')),

            Action::make('closeCelebration')
                ->label('Close Celebration')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Close Celebration')
                ->modalDescription('This will finalize the celebration, count participants, update totals and process payments.')
                ->modalSubmitActionLabel('Close Celebration')
                ->action(function () {
                    try {
                        DB::beginTransaction();

                        /** @var Celebration $celebration */
                        $celebration = $this->record;
                        $newTotalAmount = $this->recalculateTotalAmount($celebration);
                        $family = $celebration->family ?? $celebration->child?->family;

                        if (!$family) {
                            throw new Exception('Cannot find associated family for this celebration');
                        }

                        $currentPaidAmount = $celebration->paid_amount;

                        if ($currentPaidAmount < $newTotalAmount) {
                            $amountDue = $newTotalAmount - $currentPaidAmount;
                            $celebration->children_count = $celebration->invitations()->count();
                            $celebration->total_amount = $newTotalAmount;
                            $celebration->save();
                            DB::commit();

                            Notification::make()
                                ->title('Additional Payment Required')
                                ->body("The recalculated total amount is AED " . number_format($newTotalAmount / 100, 2) .
                                    ". An additional payment of AED " . number_format($amountDue / 100, 2) . " is required.")
                                ->danger()
                                ->persistent()
                                ->send();

                            $this->redirect(route('filament.admin.resources.celebrations.view', $celebration->id));
                            return;
                        }

                        if ($currentPaidAmount > $newTotalAmount) {
                            $refundAmount = $currentPaidAmount - $newTotalAmount;

                            $celebration->family->main_wallet->deposit($refundAmount, [
                                'description' => 'Refund from celebration #' . $celebration->id,
                                'type'        => 'celebration_refund'
                            ])->save();

                            $currentLoyaltyAmount = $currentPaidAmount * $celebration->package->cashback_percentage / 100;
                            $celebration->family->loyalty_wallet->withdraw($currentLoyaltyAmount, [
                                'description' => 'Loyalty points deduction for celebration #' . $celebration->id,
                                'type'        => 'celebration_loyalty_deduction'
                            ])->save();

                            $newLoyaltyAmount = $newTotalAmount * $celebration->package->cashback_percentage / 100;
                            $celebration->family->loyalty_wallet->deposit($newLoyaltyAmount, [
                                'description' => 'Loyalty points for celebration #' . $celebration->id,
                                'type'        => 'celebration_loyalty_deposit'
                            ])->save();

                            Notification::make()
                                ->title('Celebration Closed Successfully')
                                ->body("The celebration has been closed. A refund of AED " .
                                    number_format($refundAmount / 100, 2) .
                                    " has been returned to the wallet.")
                                ->success()
                                ->send();

                            $celebration->paid_amount = $newTotalAmount;
                        } else {
                            Notification::make()
                                ->title('Celebration Closed Successfully')
                                ->body("The celebration has been closed.")
                                ->success()
                                ->send();
                        }

                        $celebration->total_amount = $newTotalAmount;
                        $celebration->children_count = $celebration->invitations()->count();
                        $celebration->closed_at = now();
                        $celebration->completed = true;
                        $celebration->save();

                        DB::commit();

                        $this->redirect(route('filament.admin.resources.celebrations.view', $celebration->id));
                    } catch (Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Failed to close celebration')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
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
     * Recalculate the total amount based on package pricing and actual attendees
     *
     * @param Celebration $celebration
     * @return int Total amount in cents
     * @throws ExceptionInterface
     */
    protected function recalculateTotalAmount(Celebration $celebration): int
    {
        $totalAmount = $celebration->total_amount;
        $celebrationCurrentChildrenCount = $celebration->children_count;
        $packagePrice = (Carbon::parse($celebration->celebration_date)->isWeekend() ? $celebration->package->weekend_price : $celebration->package->weekday_price) * 100;

        $childrenAmount = $packagePrice * $celebrationCurrentChildrenCount;

        $celebrationPriceWithoutChildren = $totalAmount - $childrenAmount;

        $realChildrenCount = $celebration->invitations()->count();
        $realChildrenAmount = $packagePrice * $realChildrenCount;

        return $celebrationPriceWithoutChildren + $realChildrenAmount;
    }
}
