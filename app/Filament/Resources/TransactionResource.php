<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

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
