<?php

namespace App\Filament\Resources;

use App\Enums\ProductTypeEnum;
use App\Filament\Resources\ProductPackageResource\Pages;
use App\Filament\Resources\ProductPackageResource\RelationManagers;
use App\Models\ProductPackage;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductPackageResource extends Resource
{
    protected static ?string $model = ProductPackage::class;

    protected static ?string $navigationGroup = 'Product Management';

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewProductPackages');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Details')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('product_id')
                                    ->relationship(
                                        name: 'product',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query->available()->where('type', ProductTypeEnum::PACKAGE),
                                    )
                                    ->required()
                                    ->searchable(['name', 'description'])
                                    ->preload(),
                                Forms\Components\TextInput::make('product_quantity')
                                    ->label('Quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                                Forms\Components\Toggle::make('is_available')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_public')
                                    ->default(false),
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
                                Forms\Components\TextInput::make('price')
                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : '')
                                    ->dehydrateStateUsing(fn(string $state): string => app(FormatterServiceInterface::class)->intValue($state, 2))
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('discount_price')
                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : '')
                                    ->dehydrateStateUsing(fn(?string $state): ?string => $state ? app(FormatterServiceInterface::class)->intValue($state, 2) : null)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0),
                                TextInput::make('cashback_amount')
                                    ->formatStateUsing(fn(?string $state): string => $state ? app(FormatterServiceInterface::class)->floatValue($state, 2) : '')
                                    ->dehydrateStateUsing(fn(?string $state): ?string => $state ? app(FormatterServiceInterface::class)->intValue($state, 2) : null)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0),
                            ])
                    ]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(function (ProductPackage $record) {
                if ($record->trashed()) {
                    return null;
                }
                return self::getUrl('view', ['record' => $record]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->fontFamily(FontFamily::Mono)
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_price')
                    ->fontFamily(FontFamily::Mono)
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('cashback_amount')
                    ->fontFamily(FontFamily::Mono)
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_quantity')
                    ->label('Quantity')
                    ->fontFamily(FontFamily::Mono)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean(),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->hidden(fn(ProductPackage $record) => $record->trashed()),
                DeleteAction::make(),
                Action::make('restore')
                    ->requiresConfirmation()
                    ->action(fn(ProductPackage $record) => $record->restore())
                    ->hidden(fn(ProductPackage $record) => !$record->trashed())
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductPackages::route('/'),
            'create' => Pages\CreateProductPackage::route('/create'),
            'edit' => Pages\EditProductPackage::route('/{record}/edit'),
            'view' => Pages\ViewProductPackage::route('/{record}'),
        ];
    }
}
