<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use App\Models\Celebration;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                ->visible(fn($record): bool => auth()->guard('admin')->user()->can('updateCelebrations') && is_null($record->closed_at)),

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

            Action::make('makePayment')
                ->label('Make Payment')
                ->icon('heroicon-o-credit-card')
                ->color('primary')
                ->modalWidth('lg')
                ->form([
                    Section::make('Payment Information')
                        ->schema([
                            Grid::make()
                                ->schema([
                                    Placeholder::make('total_amount_display')
                                        ->label('Total Amount')
                                        ->content(fn(Celebration $record) => 'AED ' . number_format($record->total_amount / 100, 2)),

                                    Placeholder::make('paid_amount_display')
                                        ->label('Already Paid')
                                        ->content(fn(Celebration $record) => 'AED ' . number_format($record->paid_amount / 100, 2)),

                                    Placeholder::make('remaining_amount_display')
                                        ->label('Remaining Amount')
                                        ->content(function(Celebration $record) {
                                            $remaining = ($record->total_amount - $record->paid_amount) / 100;
                                            return 'AED ' . number_format($remaining, 2);
                                        }),

                                    Placeholder::make('wallet_balance_display')
                                        ->label('Main Wallet Balance')
                                        ->content(function(Celebration $record) {
                                            $balance = $record->family->main_wallet->balance / 100;
                                            return 'AED ' . number_format($balance, 2);
                                        }),
                                ]),

                            Placeholder::make('needed_amount_display')
                                ->label('Minimum Amount Needed to Add to Wallet')
                                ->content(function(Celebration $record) {
                                    $remaining = ($record->total_amount - $record->paid_amount) / 100;
                                    $balance = $record->family->main_wallet->balance / 100;
                                    $needed = max(0, $remaining - $balance);
                                    return 'AED ' . number_format($needed, 2);
                                })
                                ->visible(function(Celebration $record) {
                                    $remaining = $record->total_amount - $record->paid_amount;
                                    $balance = $record->family->main_wallet->balance;
                                    return $remaining > $balance;
                                }),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'wallet' => 'Wallet',
                                    'card' => 'Card',
                                    'cash' => 'Cash',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, Celebration $record) {
                                    if ($state === 'wallet') {
                                        $remaining = ($record->total_amount - $record->paid_amount) / 100;
                                        $balance = $record->family->main_wallet->balance / 100;
                                        $needed = max(0, $remaining - $balance);

                                        if ($needed > 0) {
                                            $set('wallet_topup_amount', $needed);
                                        }
                                    }
                                }),

                            TextInput::make('wallet_topup_amount')
                                ->label('Amount to Add to Wallet')
                                ->prefix('AED')
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->minValue(function(Celebration $record) {
                                    $remaining = ($record->total_amount - $record->paid_amount) / 100;
                                    $balance = $record->family->main_wallet->balance / 100;
                                    return max(0, $remaining - $balance);
                                })
                                ->helperText(function(Celebration $record) {
                                    $remaining = ($record->total_amount - $record->paid_amount) / 100;
                                    $balance = $record->family->main_wallet->balance / 100;
                                    $needed = max(0, $remaining - $balance);
                                    return "Minimum required: AED " . number_format($needed, 2) . ". You can add more if needed.";
                                })
                                ->visible(function($get, Celebration $record) {
                                    if ($get('payment_method') !== 'wallet') {
                                        return false;
                                    }
                                    $remaining = $record->total_amount - $record->paid_amount;
                                    $balance = $record->family->main_wallet->balance;
                                    return $remaining > $balance;
                                }),

                            TextInput::make('amount')
                                ->label('Amount Customer Paid')
                                ->prefix('AED')
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->visible(fn($get) => in_array($get('payment_method'), ['card', 'cash']))
                                ->helperText('Enter the amount the customer paid'),
                        ])
                ])
                ->action(function (array $data, $record) {
                    $this->processPayment($data, $record);
                })
                ->visible(fn($record): bool => !is_null($record->closed_at) && $record->total_amount > $record->paid_amount),

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
        ];
    }

    /**
     * Process payment for the celebration
     */
    protected function processPayment(array $data, Celebration $celebration): void
    {
        try {
            DB::beginTransaction();

            $paymentMethod = $data['payment_method'];
            $family = $celebration->family;
            $package = $celebration->package;
            $remainingAmount = $celebration->total_amount - $celebration->paid_amount;

            if ($paymentMethod === 'wallet') {
                $currentBalance = $family->mainWallet->balance;

                if ($currentBalance < $remainingAmount) {
                    $topupAmount = $data['wallet_topup_amount'] * 100;

                    if ($topupAmount <= 0) {
                        throw new \Exception('Top-up amount must be greater than 0');
                    }

                    $minimumRequired = $remainingAmount - $currentBalance;
                    if ($topupAmount < $minimumRequired) {
                        throw new \Exception('Top-up amount is insufficient. Minimum required: AED ' . number_format($minimumRequired / 100, 2));
                    }

                    $family->main_wallet->deposit($topupAmount);
                }

                $family->main_wallet->withdraw($remainingAmount,[
                    'description' => 'Withdrawal for celebration #' . $celebration->id,
                ]);

                $celebration->paid_amount = $celebration->total_amount;
                $celebration->save();

                $cashbackAmount = ($remainingAmount * $package->cashback_percentage) / 100;
                if ($cashbackAmount > 0) {
                    $family->loyalty_wallet->deposit($cashbackAmount, [
                        'description' => 'Cashback for celebration #' . $celebration->id,
                    ]);
                }

                $excessAmount = isset($topupAmount) ? ($topupAmount - ($remainingAmount - $currentBalance)) : 0;

                $message = 'Payment processed successfully via wallet';
                if (isset($topupAmount)) {
                    $message .= '. Wallet topped up with AED ' . number_format($topupAmount / 100, 2);
                    if ($excessAmount > 0) {
                        $message .= '. Excess amount AED ' . number_format($excessAmount / 100, 2) . ' added to main wallet';
                    }
                }

            } else {
                $paidAmount = $data['amount'] * 100;

                if ($paidAmount <= 0) {
                    throw new \Exception('Payment amount must be greater than 0');
                }

                $family->main_wallet->deposit($paidAmount, [
                    'description' => 'Payment for celebration #' . $celebration->id,
                ]);

                $amountToWithdraw = min($paidAmount, $remainingAmount);
                $family->main_wallet->withdraw($amountToWithdraw, [
                    'description' => 'Withdrawal for celebration #' . $celebration->id,
                ]);

                $celebration->paid_amount += $amountToWithdraw;
                $celebration->save();

                $cashbackAmount = ($amountToWithdraw * $package->cashback_percentage) / 100;
                if ($cashbackAmount > 0) {
                    $family->loyalty_wallet->deposit($cashbackAmount, [
                        'description' => 'Cashback for celebration #' . $celebration->id,
                    ]);
                }

                $method = ucfirst($paymentMethod);
                $message = "Payment of AED " . number_format($paidAmount / 100, 2) . " processed successfully via {$method}";

                $excessAmount = $paidAmount - $amountToWithdraw;
                if ($excessAmount > 0) {
                    $message .= '. Excess amount AED ' . number_format($excessAmount / 100, 2) . ' added to main wallet';
                }
            }

            if (isset($cashbackAmount) && $cashbackAmount > 0) {
                $message .= '. Cashback of AED ' . number_format($cashbackAmount / 100, 2) . ' added to loyalty wallet';
            }

            DB::commit();

            Notification::make()
                ->title('Payment Successful')
                ->body($message)
                ->success()
                ->send();

            $this->redirect(route('filament.admin.resources.celebrations.view', $celebration->id));

        } catch (Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->title('Payment Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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
