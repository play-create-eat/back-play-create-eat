<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Family;
use Bavix\Wallet\Models\Transaction;
use Exception;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-m-rectangle-stack';
    protected static ?string $navigationGroup = 'Wallets';

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')->label('ID')->searchable(),
                TextColumn::make('wallet.name')->label('Wallet Type'),
                TextColumn::make('wallet.holder.name')->label('Family')->searchable(),
                TextColumn::make('type')->sortable()->searchable(),
                TextColumn::make('amount')->money('AED', true),
                TextColumn::make('meta->description')->label('Description')->wrap(),
                TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('wallet_id')
                    ->relationship('wallet', 'name')
                    ->label('Wallet Type'),

                SelectFilter::make('type')
                    ->options([
                        'deposit'  => 'Deposit',
                        'withdraw' => 'Withdraw',
                        'transfer' => 'Transfer',
                    ])
                    ->label('Transaction Type'),

                SelectFilter::make('wallet.holder_id')
                    ->label('Family')
                    ->options(Family::pluck('name', 'id')),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactions::route('/'),
        ];
    }
}
