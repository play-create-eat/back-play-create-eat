<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Services\TransactionCancellationService;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Wallet Transactions';

    protected static ?string $navigationGroup = 'Wallet Management';

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewWalletTransactions');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->label('Transaction ID')
                    ->disabled(),
                Forms\Components\Select::make('wallet_id')
                    ->relationship('wallet', 'name')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'deposit' => 'Deposit',
                        'withdraw' => 'Withdraw',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->helperText('Amount in fils (multiply by 100)'),
                Forms\Components\KeyValue::make('meta')
                    ->label('Metadata'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('payable.name')
                    ->label('Family/User')
                    ->searchable()
                    ->getStateUsing(function (Transaction $record) {
                        if ($record->payable_type === 'App\Models\Family') {
                            return $record->payable->name ?? 'N/A';
                        }
                        return 'N/A';
                    }),
                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Wallet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->colors([
                        'success' => 'deposit',
                        'danger' => 'withdraw',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (Transaction $record) {
                        $cancellationService = app(TransactionCancellationService::class);
                        if ($cancellationService->isCancelled($record)) {
                            return 'Cancelled';
                        }
                        return 'Active';
                    })
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Cancelled',
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('AED')
                    ->getStateUsing(function (Transaction $record) {
                        $amount = $record->amount / 100;
                        return number_format($amount, 2);
                    }),
                Tables\Columns\TextColumn::make('meta.payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->colors([
                        'primary' => 'card',
                        'warning' => 'cash',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'card' => 'Card Payment',
                        'cash' => 'Cash Payment',
                        default => 'N/A'
                    }),
                Tables\Columns\TextColumn::make('meta.description')
                    ->label('Description')
                    ->wrap()
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'deposit' => 'Deposits',
                        'withdraw' => 'Withdrawals',
                    ])
                    ->label('Transaction Type'),

                SelectFilter::make('payment_method')
                    ->options([
                        'card' => 'Card Payment',
                        'cash' => 'Cash Payment',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereJsonContains('meta->payment_method', $data['value']);
                    })
                    ->label('Payment Method'),

                SelectFilter::make('wallet_id')
                    ->relationship('wallet', 'name')
                    ->label('Wallet'),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'cancelled' => 'Cancelled',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        $cancellationService = app(TransactionCancellationService::class);
                        $cancelledUuids = $cancellationService->getCancelledTransactionUuids();

                        if ($data['value'] === 'cancelled') {
                            return $query->whereIn('uuid', $cancelledUuids)
                                ->orWhere('meta->cancelled', true);
                        } else {
                            return $query->whereNotIn('uuid', $cancelledUuids)
                                ->where(function ($q) {
                                    $q->whereNull('meta->cancelled')
                                      ->orWhere('meta->cancelled', false);
                                });
                        }
                    })
                    ->label('Transaction Status'),

                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel Transaction')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Transaction $record) {
                        $cancellationService = app(TransactionCancellationService::class);
                        return $cancellationService->isCancellable($record) &&
                               auth()->guard('admin')->user()->can('cancelTransactions');
                    })
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->placeholder('Please provide a reason for cancelling this transaction...'),
                    ])
                    ->action(function (Transaction $record, array $data) {
                        try {
                            $cancellationService = app(TransactionCancellationService::class);
                            $cancellationService->cancelDeposit($record, $data['reason']);

                            Notification::make()
                                ->title('Transaction Cancelled')
                                ->body('The deposit transaction has been successfully cancelled.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Cancellation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Transaction')
                    ->modalDescription('Are you sure you want to cancel this deposit transaction? This action will reverse the deposit and cannot be undone.')
                    ->modalSubmitActionLabel('Cancel Transaction'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('cancel_selected')
                    ->label('Cancel Selected Transactions')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn () => auth()->guard('admin')->user()->can('cancelTransactions'))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->placeholder('Please provide a reason for cancelling these transactions...'),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $cancellationService = app(TransactionCancellationService::class);

                        // Filter only cancellable transactions
                        $cancellableRecords = $records->filter(function (Transaction $record) use ($cancellationService) {
                            return $cancellationService->isCancellable($record);
                        });

                        if ($cancellableRecords->isEmpty()) {
                            Notification::make()
                                ->title('No Cancellable Transactions')
                                ->body('None of the selected transactions can be cancelled.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $results = $cancellationService->cancelMultipleDeposits(
                            $cancellableRecords->pluck('id')->toArray(),
                            $data['reason']
                        );

                        $successCount = count($results['successful']);
                        $failureCount = count($results['failed']);

                        if ($successCount > 0) {
                            Notification::make()
                                ->title('Transactions Cancelled')
                                ->body("{$successCount} transaction(s) were successfully cancelled.")
                                ->success()
                                ->send();
                        }

                        if ($failureCount > 0) {
                            $errorMessages = collect($results['failed'])
                                ->pluck('error')
                                ->unique()
                                ->join(', ');

                            Notification::make()
                                ->title('Some Cancellations Failed')
                                ->body("{$failureCount} transaction(s) could not be cancelled. Errors: {$errorMessages}")
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Selected Transactions')
                    ->modalDescription('Are you sure you want to cancel the selected deposit transactions? This action will reverse the deposits and cannot be undone.')
                    ->modalSubmitActionLabel('Cancel Transactions')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
