<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Cashier\Pages\ManageCelebrationChildren;
use App\Filament\Clusters\Cashier\Resources\CelebrationResource\RelationManagers\CelebrationChildrenRelationManager;
use App\Filament\Resources\CelebrationResource\Pages;
use App\Filament\Resources\CelebrationResource\RelationManagers\InvitationsRelationManager;
use App\Models\Celebration;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CelebrationResource extends Resource
{
    protected static ?string $model = Celebration::class;

    protected static ?string $navigationGroup = 'Celebration Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Celebration Details')
                    ->schema([
                        Select::make('child_id')
                            ->relationship('child', 'first_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('package_id')
                            ->relationship('package', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('theme_id')
                            ->relationship('theme', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        DateTimePicker::make('celebration_date')
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state) {
                                if ($state) {
                                    $component->state(Carbon::parse($state)->format('Y-m-d H:i'));
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if ($state) {
                                    return Carbon::parse($state)->format('Y-m-d H:i');
                                }
                                return null;
                            }),

                        TextInput::make('children_count')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        TextInput::make('parents_count')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])->columns(),

                Section::make('Menu & Cake')
                    ->schema([
                        Select::make('cake_id')
                            ->relationship('cake', 'type')
                            ->searchable()
                            ->preload(),

                        TextInput::make('cake_weight')
                            ->numeric()
                            ->step(0.5)
                            ->suffix('kg'),
                    ])->columns(3),

                Section::make('Payment Information')
                    ->schema([
                        TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('AED')
                            ->required()
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state) {
                                if (is_numeric($state)) {
                                    $component->state($state / 100);
                                }
                                if (is_null($state)) {
                                    $component->state(0);
                                }
                            })->dehydrateStateUsing(fn($state) => $state * 100),

                        TextInput::make('paid_amount')
                            ->numeric()
                            ->prefix('AED')
                            ->step(0.01)
                            ->required()
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state) {
                                if (is_numeric($state)) {
                                    $component->state($state / 100);
                                }

                                if (is_null($state)) {
                                    $component->state(0);
                                }
                            })->dehydrateStateUsing(fn($state) => $state * 100),

                        TextInput::make('min_amount')
                            ->numeric()
                            ->prefix('AED')
                            ->step(0.01)
                            ->disabled()
                            ->afterStateHydrated(function ($component, $state) {
                                if (is_numeric($state)) {
                                    $component->state($state / 100);
                                }
                                if (is_null($state)) {
                                    $component->state(0);
                                }
                            })->dehydrateStateUsing(fn($state) => $state * 100),
                        Actions::make([
                            Actions\Action::make('processCelebrationPayment')
                                ->label('Process Payment')
                                ->icon('heroicon-o-credit-card')
                                ->button()
                                ->color('primary')
                                ->modalHeading('Process Celebration Payment')
                                ->modalDescription('Record a payment for this celebration')
                                ->modalSubmitActionLabel('Process Payment')
                                ->form([
                                    TextInput::make('payment_amount')
                                        ->label('Payment Amount')
                                        ->prefix('AED')
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->required()
                                        ->afterStateHydrated(function ($component, $record) {
                                            if ($record) {
                                                $remaining = ($record->total_amount - $record->paid_amount) / 100;
                                                $component->state(max($remaining, 0));
                                            }
                                        })
                                        ->minValue(function ($record) {
                                            if ($record && $record->min_amount) {
                                                return $record->min_amount / 100;
                                            }
                                            return 0.01;
                                        }),
                                    Select::make('payment_method')
                                        ->label('Payment Method')
                                        ->options([
                                            'card' => 'Card Payment',
                                            'cash' => 'Cash Payment',
                                        ])
                                        ->required(),
                                ])->action(function (array $data, $record, Action $action) {
                                    $paymentAmount = (float)$data['payment_amount'] * 100;
                                    $paymentMethod = $data['payment_method'];
                                    try {
                                        $record->paid_amount += $paymentAmount;
                                        $record->save();

                                        Notification::make()
                                            ->title('Payment processed successfully')
                                            ->body("Added AED " . number_format($data['payment_amount'], 2) . " payment via {$paymentMethod}")
                                            ->success()
                                            ->send();

                                        $record->refresh();

                                    } catch (Exception $e) {
                                        Notification::make()
                                            ->title('Payment failed')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }

                                })->visible(fn($record) => $record && ($record->total_amount > $record->paid_amount))
                        ])->alignment('right')->verticallyAlignEnd(),
                    ])->columns(4),

                Section::make('Progress')
                    ->schema([
                        TextInput::make('current_step')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(),

                        Toggle::make('completed')
                            ->inline(false)
                            ->disabled(),
                    ])->columns(3),

            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('child.first_name')
                    ->label('Child')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('theme.name')
                    ->label('Theme')
                    ->searchable(),

                Tables\Columns\TextColumn::make('celebration_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('children_count')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parents_count')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('aed')
                    ->formatStateUsing(function ($state) {
                        if (is_numeric($state)) {
                            return $state / 100;
                        }
                        return 0;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('aed')
                    ->formatStateUsing(function ($state) {
                        if (is_numeric($state)) {
                            return $state / 100;
                        }
                        return 0;
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('completed')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('theme')
                    ->relationship('theme', 'name'),

                Filter::make('upcoming')
                    ->query(fn($query) => $query->where('celebration_date', '>=', now()))
                    ->toggle(),

                Filter::make('completed')
                    ->query(fn($query) => $query->where('completed', true))
                    ->toggle(),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvitationsRelationManager::class,
            CelebrationChildrenRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCelebrations::route('/'),
            'create' => Pages\CreateCelebration::route('/create'),
            'view'   => Pages\ViewCelebration::route('/{record}'),
            'edit'   => Pages\EditCelebration::route('/{record}/edit'),
            'manage-invited-children' => Pages\ManageInvitedChildren::route('/{record}/invited-children'),
            'cashier-manage-children' => ManageCelebrationChildren::route('/{record}/cashier/children'),
        ];
    }
}
