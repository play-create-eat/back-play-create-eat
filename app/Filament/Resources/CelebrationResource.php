<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Cashier\Pages\ManageCelebrationChildren;
use App\Filament\Clusters\Cashier\Resources\CelebrationResource\RelationManagers\CelebrationChildrenRelationManager;
use App\Filament\Resources\CelebrationResource\Pages;
use App\Filament\Resources\CelebrationResource\RelationManagers\BookingsRelationManager;
use App\Models\Cake;
use App\Models\Celebration;
use App\Services\CelebrationPricingService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Throwable;

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
                        Select::make('family_id')
                            ->relationship('family', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->relationship('user.profile', 'phone_number')
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
                        Actions::make([
                            Actions\Action::make('printMenu')
                                ->label('Print Menu')
                                ->icon('heroicon-o-printer')
                                ->color('info')
                                ->button()
                                ->url(fn($record) => $record ? URL::signedRoute(
                                    'celebration.print-menu',
                                    ['celebration' => $record]
                                ) : null)
                                ->openUrlInNewTab()
                                ->visible(fn($record) => $record && ($record->cake || ($record->cart && $record->cart->items->isNotEmpty())))
                        ])->alignment('right')->columnSpanFull(),

                        Select::make('cake_id')
                            ->relationship('cake', 'type')
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if ($state) {
                                    $cake = Cake::find($state);
                                    if ($cake) {
                                        $set('cake_price_display', number_format($cake->price_per_kg, 2));
                                    }
                                }
                            }),

                        TextInput::make('cake_weight')
                            ->numeric()
                            ->step(0.5)
                            ->suffix('kg'),

                        TextInput::make('cake_price_per_kg')
                            ->label('Price per KG')
                            ->prefix('AED')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->cake) {
                                    $component->state(number_format($record->cake->price_per_kg, 2));
                                } else {
                                    $component->state('0.00');
                                }
                            }),

                        TextInput::make('cake_total_price')
                            ->label('Total Cake Cost')
                            ->prefix('AED')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->cake && $record->cake_weight) {
                                    $totalCost = $record->cake->price_per_kg * $record->cake_weight;
                                    $component->state(number_format($totalCost, 2));
                                } else {
                                    $component->state('0.00');
                                }
                            }),

                        Placeholder::make('menu_items_display')
                            ->label('Menu Selection')
                            ->content(function ($record) {
                                if (!$record) {
                                    return 'No menu items selected';
                                }

                                $celebration = $record->load([
                                    'cart.items.menuItem.tags',
                                    'cart.items.menuItem.type',
                                    'cart.items.modifiers.modifierOption'
                                ]);

                                if (!$celebration->cart || $celebration->cart->items->isEmpty()) {
                                    return 'No menu items selected';
                                }

                                $html = '<div class="space-y-4">';

                                $itemsByAudience = $celebration->cart->items->groupBy('audience');

                                if (isset($itemsByAudience['children'])) {
                                    $html .= '<div class="border rounded-lg p-3 bg-blue-50 border-blue-200">';
                                    $html .= '<div class="flex items-center justify-between mb-3">';
                                    $html .= '<h4 class="font-semibold text-sm text-blue-800">Children\'s Menu</h4>';
                                    $html .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">';
                                    $html .= '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
                                    $html .= 'Included in Package';
                                    $html .= '</span>';
                                    $html .= '</div>';

                                    foreach ($itemsByAudience['children'] as $item) {
                                        $html .= '<div class="mb-2 p-2 bg-white rounded border-l-4 border-blue-500">';
                                        $html .= '<div class="flex justify-between items-start">';
                                        $html .= '<div class="flex-1">';
                                        $html .= '<p class="font-medium text-sm">' . $item->menuItem->name . '</p>';
                                        $html .= '<p class="text-xs text-gray-600">Qty: ' . $item->quantity . ' (per child)</p>';

                                        if ($item->menuItem->tags->isNotEmpty()) {
                                            $html .= '<div class="flex flex-wrap gap-1 mt-1">';
                                            foreach ($item->menuItem->tags as $tag) {
                                                $html .= '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium" style="background-color: ' . $tag->color . '20; color: ' . $tag->color . ';">';
                                                $html .= $tag->name;
                                                $html .= '</span>';
                                            }
                                            $html .= '</div>';
                                        }

                                        if ($item->modifiers->isNotEmpty()) {
                                            $html .= '<div class="mt-2 pl-3 border-l-2 border-gray-200">';
                                            $html .= '<p class="text-xs font-medium text-gray-700">Modifiers:</p>';
                                            foreach ($item->modifiers as $modifier) {
                                                $html .= '<p class="text-xs text-gray-600">+ ' . $modifier->modifierOption->name . '</p>';
                                            }
                                            $html .= '</div>';
                                        }

                                        $html .= '</div>';
                                        $html .= '<div class="text-right">';
                                        $html .= '<span class="text-xs font-medium text-green-600">Included</span>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                    }

                                    $html .= '<div class="flex justify-between items-center mt-2 pt-2 border-t border-blue-300">';
                                    $html .= '<span class="font-semibold text-sm text-blue-800">Children\'s Menu Total:</span>';
                                    $html .= '<span class="font-bold text-sm text-green-600">Included in Package</span>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }

                                if (isset($itemsByAudience['parents'])) {
                                    $parentsTotal = 0;
                                    $html .= '<div class="border rounded-lg p-3 bg-orange-50 border-orange-200">';
                                    $html .= '<div class="flex items-center justify-between mb-3">';
                                    $html .= '<h4 class="font-semibold text-sm text-orange-800">Parents\' Additional Menu</h4>';
                                    $html .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">';
                                    $html .= '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>';
                                    $html .= 'Additional Cost';
                                    $html .= '</span>';
                                    $html .= '</div>';

                                    foreach ($itemsByAudience['parents'] as $item) {
                                        $basePrice = $item->menuItem->price * $item->quantity;
                                        $modifierTotal = 0;

                                        $html .= '<div class="mb-2 p-2 bg-white rounded border-l-4 border-orange-500">';
                                        $html .= '<div class="flex justify-between items-start">';
                                        $html .= '<div class="flex-1">';
                                        $html .= '<p class="font-medium text-sm">' . $item->menuItem->name . '</p>';
                                        $html .= '<p class="text-xs text-gray-600">Qty: ' . $item->quantity . ' × AED ' . number_format($item->menuItem->price, 2) . '</p>';

                                        if ($item->menuItem->tags->isNotEmpty()) {
                                            $html .= '<div class="flex flex-wrap gap-1 mt-1">';
                                            foreach ($item->menuItem->tags as $tag) {
                                                $html .= '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium" style="background-color: ' . $tag->color . '20; color: ' . $tag->color . ';">';
                                                $html .= $tag->name;
                                                $html .= '</span>';
                                            }
                                            $html .= '</div>';
                                        }

                                        if ($item->modifiers->isNotEmpty()) {
                                            $html .= '<div class="mt-2 pl-3 border-l-2 border-gray-200">';
                                            $html .= '<p class="text-xs font-medium text-gray-700">Modifiers:</p>';
                                            foreach ($item->modifiers as $modifier) {
                                                $modPrice = $modifier->modifierOption->price * $item->quantity;
                                                $modifierTotal += $modPrice;
                                                $html .= '<p class="text-xs text-gray-600">+ ' . $modifier->modifierOption->name . ' (AED ' . number_format($modifier->modifierOption->price, 2) . ' × ' . $item->quantity . ')</p>';
                                            }
                                            $html .= '</div>';
                                        }

                                        $html .= '</div>';
                                        $itemTotal = $basePrice + $modifierTotal;
                                        $parentsTotal += $itemTotal;
                                        $html .= '<div class="text-right">';
                                        $html .= '<p class="font-semibold text-sm text-orange-600">AED ' . number_format($itemTotal, 2) . '</p>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                    }

                                    $html .= '<div class="flex justify-between items-center mt-2 pt-2 border-t border-orange-300">';
                                    $html .= '<span class="font-semibold text-sm text-orange-800">Parents\' Menu Total:</span>';
                                    $html .= '<span class="font-bold text-sm text-orange-600">AED ' . number_format($parentsTotal, 2) . '</span>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }

                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),

                        Placeholder::make('features_display')
                            ->label('Selected Features')
                            ->content(function ($record) {
                                if (!$record || $record->features->isEmpty()) {
                                    return 'No additional features selected';
                                }

                                $html = '<div class="space-y-2">';
                                $totalFeaturesCost = 0;

                                foreach ($record->features as $feature) {
                                    $featureCost = $feature->price;
                                    $totalFeaturesCost += $featureCost;

                                    $html .= '<div class="flex justify-between items-center p-2 bg-green-50 rounded border-l-4 border-green-500">';
                                    $html .= '<div>';
                                    $html .= '<p class="font-medium text-sm">' . $feature->title . '</p>';
                                    $html .= '<p class="text-xs text-gray-600">' . $feature->description . '</p>';
                                    $html .= '</div>';
                                    $html .= '<p class="font-semibold text-sm">AED ' . number_format($featureCost, 2) . '</p>';
                                    $html .= '</div>';
                                }

                                $html .= '<div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-300">';
                                $html .= '<span class="font-semibold text-sm">Features Total:</span>';
                                $html .= '<span class="font-bold text-sm">AED ' . number_format($totalFeaturesCost, 2) . '</span>';
                                $html .= '</div>';
                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),

                    ])->columns(2),

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
                            Actions\Action::make('recalculatePrice')
                                ->label('Refresh Price')
                                ->icon('heroicon-o-arrow-path')
                                ->button()
                                ->color('primary')
                                ->action(function ($record) {
                                    if (!$record) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('No celebration record found.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        DB::beginTransaction();

                                        $pricingService = app(CelebrationPricingService::class);
                                        $invitedChildrenCount = $record->invitations()->count();
                                        $package = $record->package;

                                        if ($invitedChildrenCount >= $package->min_children) {
                                            $pricing = $pricingService->recalculateAndUpdate($record, $invitedChildrenCount);

                                            DB::commit();

                                            Notification::make()
                                                ->title('Price Recalculated Successfully')
                                                ->body("Total amount updated to AED " . number_format($pricing['total_cost'] / 100, 2) . " based on {$invitedChildrenCount} invited children.")
                                                ->success()
                                                ->send();

                                            redirect()->to(request()->header('Referer'));
                                        } else {
                                            DB::rollBack();

                                            Notification::make()
                                                ->title('Cannot Recalculate Price')
                                                ->body("Invited children count ($invitedChildrenCount) is less than package minimum ($package->min_children). Price remains unchanged.")
                                                ->warning()
                                                ->send();
                                        }
                                    } catch (Throwable $e) {
                                        DB::rollBack();

                                        Notification::make()
                                            ->title('Failed to recalculate price')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(fn($record) => $record && is_null($record->closed_at))
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

                Tables\Columns\TextColumn::make('family.name')
                    ->label('Family')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.profile.phone_number')
                    ->label('User Phone')
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
                    ->toggleable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('theme')
                    ->relationship('theme', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => auth()->guard('admin')->user()->can('updateCelebrations')),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            CelebrationChildrenRelationManager::class,
            BookingsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'                   => Pages\ListCelebrations::route('/'),
            'view'                    => Pages\ViewCelebration::route('/{record}'),
            'edit'                    => Pages\EditCelebration::route('/{record}/edit'),
            'manage-invited-children' => Pages\ManageInvitedChildren::route('/{record}/invited-children'),
            'cashier-manage-children' => ManageCelebrationChildren::route('/{record}/cashier/children'),
        ];
    }
}
