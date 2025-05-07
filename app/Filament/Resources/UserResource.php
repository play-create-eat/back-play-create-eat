<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('profile.phone_number')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->profile?->phone_number ?? '');
                                    }),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(),
                                Forms\Components\TextInput::make('full_name')
                                    ->label('Full Name')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->full_name ?? '');
                                    }),
                            ])->columns(),
                    ]),

                Section::make('Family Information')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('family.name')
                                    ->label('Family')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->family?->name ?? 'No Family');
                                    }),
                                Forms\Components\TextInput::make('family.stripe_customer_id')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->family?->stripe_customer_id);
                                    }),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('viewFamily')
                                        ->label('View Family')
                                        ->icon('heroicon-o-arrow-top-right-on-square')
                                        ->button()
                                        ->color('primary')
                                        ->url(fn ($record) => $record->family_id ? FamilyResource::getUrl('view', ['record' => $record->family_id]) : null)
                                        ->visible(fn ($record) => $record->family_id !== null),
                                ])->alignment('right')->verticallyAlignEnd()
                            ])->columns(3),
                        Forms\Components\Grid::make()
                            ->schema([
                                TextInput::make('main_wallet_balance')
                                    ->label('Main Wallet')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->family?->main_wallet?->balance / 100 ?? '0.00');
                                    }),
                                TextInput::make('loyalty_wallet_balance')
                                    ->label('Loyalty Wallet')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        $component->state($record->family?->loyalty_wallet?->balance / 100 ?? '0.00');
                                    }),
                                Actions::make([
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
                                            if (!$record->family) {
                                                Notification::make()
                                                    ->title('No Family Found')
                                                    ->body('This user does not belong to any family')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $wallet = match ($data['wallet_type']) {
                                                'loyalty' => $record->family->loyalty_wallet,
                                                default => $record->family->main_wallet,
                                            };


                                            $walletType = $data['wallet_type'] === 'main' ? 'Main' : 'Loyalty';
                                            $paymentMethod = $data['payment_method'] === 'card' ? 'card' : 'cash';

                                            DB::beginTransaction();
                                            try {
                                                $wallet->deposit((float) $data['amount'] * 100, [
                                                    'description' => "Admin top up your wallet using $paymentMethod payment"
                                                ]);

                                                Db::commit();

                                                $record->refresh();
                                                $record->load('family');

                                                $form->fill([
                                                    'main_wallet_balance' => $record->family?->main_wallet?->balance / 100 ?? '0.00',
                                                    'loyalty_wallet_balance' => $record->family?->loyalty_wallet?->balance / 100 ?? '0.00',
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
                                        })->visible(fn($record) => $record->family !== null)
                                ])->alignment('right')->verticallyAlignEnd()
                            ])->columns(3),
                    ]),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('profile.phone_number')
                    ->label('Phone Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name'),
                Tables\Columns\TextColumn::make('family.name')
                    ->label('Family')
                    ->sortable()
                    ->placeholder('No Family'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view'  => Pages\ViewUser::route('/{record}'),
        ];
    }
}
