<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageDiscountResource\Pages;
use App\Models\PackageDiscount;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PackageDiscountResource extends Resource
{
    protected static ?string $model = PackageDiscount::class;

    protected static ?string $navigationGroup = 'Celebration Management';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewPackageDiscounts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Discount Information')
                    ->schema([
                        Forms\Components\Select::make('package_id')
                            ->label('Package')
                            ->options(Package::pluck('name', 'id'))
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Discount Details')
                    ->schema([
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Discount Percentage (%)')
                            ->numeric()
                            ->rules(['min:0', 'max:100'])
                            ->suffix('%'),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Fixed Discount Amount')
                            ->numeric()
                            ->rules(['min:0'])
                            ->prefix('AED'),
                    ])->description('Set either percentage OR fixed amount, not both'),

                Forms\Components\Section::make('Date Range')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->after('start_date'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('package.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('package_id')
                    ->label('Package')
                    ->options(Package::pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackageDiscounts::route('/'),
            'create' => Pages\CreatePackageDiscount::route('/create'),
            'edit' => Pages\EditPackageDiscount::route('/{record}/edit'),
        ];
    }
}
