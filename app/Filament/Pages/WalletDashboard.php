<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PaymentMethodChart;
use App\Filament\Widgets\WalletStatsWidget;
use App\Models\Admin;
use Bavix\Wallet\Models\Transaction as WalletTransaction;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Wallet Dashboard';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.wallet-dashboard';

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewTodayAnalytics') ||
            auth()->guard('admin')->user()->can('viewFullAnalytics');
    }

    public function getHeaderWidgets(): array
    {
        return [
            WalletStatsWidget::class,
            PaymentMethodChart::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WalletTransaction::query())
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payable.name')
                    ->label('Family/User')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if ($record->payable_type === 'App\Models\Family') {
                            return $record->payable->name ?? 'N/A';
                        }
                        return 'N/A';
                    }),
                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Wallet Type')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'deposit',
                        'danger'  => 'withdraw',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->getStateUsing(function ($record) {
                        $amount = $record->amount / 100;
                        if (floor($amount) == $amount) {
                            return 'AED ' . number_format($amount);
                        } else {
                            return 'AED ' . number_format($amount, 2);
                        }
                    })
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('meta.payment_method')
                    ->label('Payment Method')
                    ->colors([
                        'primary' => 'card',
                        'warning' => 'cash',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'card' => 'Card Payment',
                        'cash' => 'Cash Payment',
                        default => 'N/A'
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('meta.description')
                    ->label('Description')
                    ->wrap()
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('meta.cashier_id')
                    ->label('Cashier')
                    ->getStateUsing(function ($record) {
                        if (isset($record->meta['cashier_id'])) {
                            $cashier = Admin::find($record->meta['cashier_id']);
                            return $cashier ? $cashier->name : 'N/A';
                        }
                        return 'N/A';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'deposit'  => 'Deposits',
                        'withdraw' => 'Withdrawals',
                    ])
                    ->label('Transaction Type'),

                SelectFilter::make('payment_method')
                    ->options([
                        'card' => 'Card Payment',
                        'cash' => 'Cash Payment',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }

                        return $query->whereJsonContains('meta->payment_method', $data['value']);
                    })
                    ->label('Payment Method'),

                SelectFilter::make('wallet_id')
                    ->relationship('wallet', 'name')
                    ->label('Wallet Type'),

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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Minimum Amount (AED)')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Maximum Amount (AED)')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '>=', $amount * 100),
                            )
                            ->when(
                                $data['max_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '<=', $amount * 100),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn($record) => view('filament.pages.transaction-details', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->searchable()
            ->striped()
            ->emptyStateHeading('No transactions found')
            ->emptyStateDescription('No wallet transactions have been recorded yet.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
