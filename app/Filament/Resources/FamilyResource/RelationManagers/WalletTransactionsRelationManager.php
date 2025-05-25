<?php

namespace App\Filament\Resources\FamilyResource\RelationManagers;

use Bavix\Wallet\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Transactions';
    }

    protected function isTablePaginationEnabled(): bool
    {
        return true;
    }

    protected function getTableQuery(): Builder
    {
        $family = $this->getOwnerRecord();
        $mainWalletId = $family->main_wallet?->id;
        $loyaltyWalletId = $family->loyalty_wallet?->id;

        if (!$mainWalletId && !$loyaltyWalletId) {
            return Transaction::query()->where('id', 0);
        }

        return Transaction::query()
            ->where(function (Builder $query) use ($mainWalletId, $loyaltyWalletId) {
                if ($mainWalletId) {
                    $query->where('wallet_id', $mainWalletId);
                }
                if ($loyaltyWalletId) {
                    $query->orWhere('wallet_id', $loyaltyWalletId);
                }
            })
            ->orderBy('created_at', 'desc');
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        $family = $this->getOwnerRecord();

        return $table
            ->recordClasses(fn ($record) => $record->type === 'deposit' ? 'bg-green-500/10' : 'bg-red-500/10')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdraw' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('wallet_id')
                    ->label('Wallet')
                    ->formatStateUsing(function ($state) use ($family) {
                        return $state == $family->main_wallet?->id ? 'Main Wallet' : 'Loyalty Wallet';
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money('AED')
                    ->formatStateUsing(fn ($state) => abs($state) / 100),
                Tables\Columns\TextColumn::make('meta.description')
                    ->label('Description')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

}
