<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FamilyResource\Pages;
use App\Filament\Resources\FamilyResource\RelationManagers;
use App\Models\Family;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Throwable;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'User Management';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stripe_customer_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChildrenRelationManager::class,
            RelationManagers\UsersRelationManager::class,
            RelationManagers\WalletTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFamilies::route('/'),
            'view'  => Pages\ViewFamily::route('/{record}'),
        ];
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('family.name')
                                    ->label('Family')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->name ?? 'No Family');
                                    }),
                                Forms\Components\TextInput::make('family.stripe_customer_id')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->stripe_customer_id);
                                    }),
                            ])->columns(),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('main_wallet_balance')
                                    ->label('Main Wallet')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->main_wallet?->balance / 100 ?? '0.00');
                                    }),
                                Forms\Components\TextInput::make('loyalty_wallet_balance')
                                    ->label('Loyalty Wallet')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->loyalty_wallet?->balance / 100 ?? '0.00');
                                    }),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('topUpWallet')
                                        ->label('Top Up Wallet')
                                        ->icon('heroicon-o-banknotes')
                                        ->button()
                                        ->color('success')
                                        ->modalHeading('Top Up Wallet')
                                        ->modalDescription('Add funds to family wallet')
                                        ->modalSubmitActionLabel('Add Funds')
                                        ->form([
                                            Select::make('wallet_type')
                                                ->label('Wallet Type')
                                                ->options([
                                                    'main'    => 'Main Wallet',
                                                    'loyalty' => 'Loyalty Wallet',
                                                ])
                                                ->required(),
                                            TextInput::make('amount')
                                                ->label('Amount')
                                                ->prefix('AED')
                                                ->numeric()
                                                ->minValue(0.01)
                                                ->required(),
                                            Select::make('payment_method')
                                                ->label('Payment Method')
                                                ->options([
                                                    'card' => 'Card Payment',
                                                    'cash' => 'Cash Payment',
                                                ])
                                                ->required(),
                                        ])->action(function (array $data, $record, Forms\Form $form): void {
                                            if (!$record) {
                                                Notification::make()
                                                    ->title('No Family Found')
                                                    ->body('This user does not belong to any family')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $wallet = match ($data['wallet_type']) {
                                                'loyalty' => $record->loyalty_wallet,
                                                default => $record->main_wallet,
                                            };


                                            $walletType = $data['wallet_type'] === 'main' ? 'Main' : 'Loyalty';
                                            $paymentMethod = $data['payment_method'] === 'card' ? 'card' : 'cash';

                                            DB::beginTransaction();
                                            try {
                                                $wallet->deposit((float)$data['amount'] * 100, [
                                                    'description' => "Admin top up your wallet using $paymentMethod payment"
                                                ]);

                                                Db::commit();

                                                $record->refresh();
                                                $record->load('family');

                                                $form->fill([
                                                    'main_wallet_balance'    => $record->main_wallet?->balance / 100 ?? '0.00',
                                                    'loyalty_wallet_balance' => $record->loyalty_wallet?->balance / 100 ?? '0.00',
                                                ]);


                                                Notification::make()
                                                    ->title('Wallet topped up successfully')
                                                    ->body("Added \${$data['amount']} to $walletType Wallet using $paymentMethod payment")
                                                    ->success()
                                                    ->send();
                                            } catch (Throwable $exception) {
                                                DB::rollBack();

                                                Notification::make()
                                                    ->title('Failed to top up wallet.')
                                                    ->body($exception->getMessage())
                                                    ->danger()
                                                    ->send();
                                            }
                                        })->visible(fn($record) => $record !== null)
                                ])->alignment('right')->verticallyAlignEnd()
                            ])->columns(3),
                    ]),
            ]);
    }
}
