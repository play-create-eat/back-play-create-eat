<?php

namespace App\Filament\Resources;

use App\Enums\ProductTypeEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Product Management';

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewProducts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Details')
                            ->schema([
                                TextInput::make('name')
                                    ->autocomplete(false)
                                    ->minLength(3)
                                    ->maxLength(255)
                                    ->required(),
                                Textarea::make('description'),
                            ]),
                        Section::make('Configuration')
                            ->schema([
                                Select::make('type')
                                    ->options(
                                        collect(ProductTypeEnum::cases())
                                            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                                            ->toArray()
                                    )
                                    ->default(ProductTypeEnum::BASIC->value)
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn() => request()->routeIs('filament.admin.resources.products.edit'))
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state === ProductTypeEnum::PACKAGE->value) {
                                            $defaultPackageDuration = collect(config("passes.durations"))
                                                ->keys()
                                                ->first(fn($value) => $value >= 1440);

                                            $set('duration_time', $defaultPackageDuration);
                                            $set('price', 0);
                                        } else {
                                            $set('price', '');
                                        }
                                    }),
                                Select::make('duration_time')
                                    ->options(config('passes.durations'))
                                    ->required()
                                    ->reactive()
                                    ->default(function () {
                                        return collect(config("passes.durations"))
                                            ->keys()
                                            ->first(fn($value) => $value >= 1440);
                                    })
                                    ->disabled(fn(callable $get) => $get('type') === ProductTypeEnum::PACKAGE->value),
                                CheckboxList::make('features')
                                    ->relationship(titleAttribute: 'name')
                                    ->columns(2)
                                    ->required(),
                            ]),

                        Section::make('Campaign')
                            ->schema([
                                Toggle::make('campaign_active')
                                    ->label('Active')
                                    ->default(false)
                                    ->reactive(),
                                DatePicker::make('campaign_start_date')
                                    ->label('Start Date')
                                    ->reactive()
                                    ->disabled(fn (callable $get) => $get('campaign_active') !== true)
                                    ->required(fn (callable $get) => $get('campaign_active') === true)
                                    ->minDate(today()),
                                DatePicker::make('campaign_end_date')
                                    ->label('End Date')
                                    ->disabled(fn (callable $get) => $get('campaign_active') !== true)
                                    ->required(fn (callable $get) => $get('campaign_active') === true)
                                    ->minDate(fn (callable $get) => $get('campaign_start_date'))
                                    ->afterOrEqual(fn (callable $get) => $get('campaign_start_date')),
                            ]),

                    ])
                    ->columnSpan(['lg' => 2]),
                Group::make()
                    ->schema([
                        Section::make('Prices')
                            ->schema([
                                TextInput::make('price')
                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : '')
                                    ->dehydrateStateUsing(fn(string $state): string => app(FormatterServiceInterface::class)->intValue($state, 2))
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('price_weekend')
                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : '')
                                    ->dehydrateStateUsing(fn(?string $state): ?string => $state ? app(FormatterServiceInterface::class)->intValue($state, 2) : null)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0),
                            ]),

                        Section::make('Discounts')
                            ->schema([
                                TextInput::make('discount_price_weekday')
                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : '')
                                    ->dehydrateStateUsing(fn(?string $state): ?string => $state ? app(FormatterServiceInterface::class)->intValue($state, 2) : null)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0),
                                TextInput::make('discount_price_weekend')
                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : '')
                                    ->dehydrateStateUsing(fn(?string $state): ?string => $state ? app(FormatterServiceInterface::class)->intValue($state, 2) : null)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0),
                                TextInput::make('discount_percent')
                                    ->numeric()
                                    ->prefix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1)
                                    ->default(0),
                            ]),

                        Section::make('Fees')
                            ->schema([
                                TextInput::make('fee_percent')
                                    ->numeric()
                                    ->prefix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1)
                                    ->default(0),
                            ]),

                        Section::make('Rewarding')
                            ->schema([
                                TextInput::make('cashback_percent')
                                    ->numeric()
                                    ->prefix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1)
                                    ->default(0),
                            ]),
                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_available')
                                    ->default(true)
                                    ->onColor('success'),
                                Toggle::make('is_extendable')
                                    ->default(false)
                                    ->onColor('success'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),


            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(function (Product $record) {
                if ($record->trashed()) {
                    return null;
                }
                return self::getUrl('view', ['record' => $record]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('type')->sortable()
                    ->badge()
                    ->formatStateUsing(fn(ProductTypeEnum $state): string => $state->label())
                    ->color(fn(ProductTypeEnum $state): string => $state->color()),
                Tables\Columns\TextColumn::make('features.name'),
                Tables\Columns\TextColumn::make('price')
                    ->fontFamily(FontFamily::Mono)
                    ->money('USD', divideBy: 100)
                    ->description(
                        function (Product $record): HtmlString {
                            $value = $record->price_weekend ? Number::currency($record->price_weekend / 100, 'USD', config('app.locale')) : '';

                            return new HtmlString(
                                '<span class="font-mono">' . $value . '</span>'
                            );
                        }
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_price_weekday')
                    ->label('Discount price')
                    ->fontFamily(FontFamily::Mono)
                    ->money('USD', divideBy: 100)
                    ->description(
                        function (Product $record): HtmlString {
                            $value = $record->discount_price_weekend ? Number::currency($record->discount_price_weekend / 100, 'USD', config('app.locale')) : '';

                            return new HtmlString(
                                '<span class="font-mono">' . $value . '</span>'
                            );
                        }
                    )
                    ->sortable(),


                Tables\Columns\TextColumn::make('cashback_percent')
                    ->label('Cashback')
                    ->fontFamily(FontFamily::Mono)
                    ->prefix('%'),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('Discount')
                    ->fontFamily(FontFamily::Mono)
                    ->prefix('%'),
                Tables\Columns\IconColumn::make('campaign_active')
                    ->label('Campaign')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_extendable')
                    ->label('Extendable')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('features')
                    ->relationship('features', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('type')
                    ->options(collect(ProductTypeEnum::cases())
                        ->mapWithKeys(fn($case) => [$case->value => $case->label()])
                        ->toArray()),
                Filter::make('is_available')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('is_available', true)),
                Filter::make('is_extendable')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('is_extendable', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->hidden(fn(Product $record) => $record->trashed()),
                DeleteAction::make(),
                Action::make('restore')
                    ->requiresConfirmation()
                    ->action(fn(Product $record) => $record->restore())
                    ->hidden(fn(Product $record) => !$record->trashed())
                    ->icon('heroicon-o-arrow-uturn-left'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => $table->getLivewire()->activeTab === 'archive'),
                    BulkAction::make('restore')
                        ->requiresConfirmation()
                        ->action(fn(Collection $records) => $records->each->restore())
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->hidden(fn() => $table->getLivewire()->activeTab !== 'archive'),
                ]),
            ]);
    }

//    public static function infolist(Infolist $infolist): Infolist
//    {
//        return $infolist
//            ->schema([
//                Infolists\Components\Group::make()
//                    ->schema([
//                        Infolists\Components\Section::make('Details')
//                            ->schema([
//                                Infolists\Components\TextEntry::make('name'),
//                                Infolists\Components\TextEntry::make('description'),
//                            ]),
//                        Infolists\Components\Section::make('Configuration')
//                            ->schema([
//                                Infolists\Components\TextEntry::make('duration_time')
//                                    ->formatStateUsing(fn(?int $state): string => $state ? CarbonInterval::minutes($state)->cascade()->forHumans() : 'N/A'),
//                                Infolists\Components\RepeatableEntry::make('features')
//                                    ->contained(false)
//                                    ->schema([
//                                        Infolists\Components\TextEntry::make('name'),
//                                    ])
//                                    ->columns(2),
//                            ]),
//                    ])
//                    ->columnSpan(['lg' => 2]),
//
//                Infolists\Components\Group::make()
//                    ->schema([
//                        Infolists\Components\Section::make('Prices')
//                            ->schema([
//                                Infolists\Components\TextEntry::make('price')
//                                    ->numeric()
//                                    ->prefix('$')
//                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : ''),
//                                Infolists\Components\TextEntry::make('price_weekend')
//                                    ->numeric()
//                                    ->prefix('$')
//                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : ''),
//                                Infolists\Components\TextEntry::make('fee_percent')
//                                    ->numeric()
//                                    ->prefix('%'),
//                            ]),
//                        Infolists\Components\Section::make('Status')
//                            ->schema([
//                                Infolists\Components\IconEntry::make('is_available'),
//                                Infolists\Components\IconEntry::make('is_extendable'),
//                            ]),
//                    ])
//                    ->columnSpan(['lg' => 1]),
//
//
//            ])
//            ->columns(3);
//    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
}
