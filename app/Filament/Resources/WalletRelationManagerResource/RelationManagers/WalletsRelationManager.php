<?php

namespace App\Filament\Resources\WalletRelationManagerResource\RelationManagers;

use App\Filament\Resources\TransactionRelationManagerResource\RelationManagers\TransactionRelationManager;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WalletsRelationManager extends RelationManager
{
    protected static string $relationship = 'wallets';


    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Wallets';
    }

    public static function getRelations(): array
    {
        return [
            TransactionRelationManager::class,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Wallet Type'),
                TextColumn::make('slug'),
                TextColumn::make('balance')->label('Balance')->money('AED', true),
            ])
            ->actions([
                Action::make('view_transactions')
                    ->label('View Transactions')
                    ->icon('heroicon-m-rectangle-stack')
                    ->url(fn($record) => route('filament.admin.resources.transactions.index', [
                        'tableFilters[wallet_id][value]' => $record->id
                    ]))
            ]);
    }
}
