<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationGroup = 'Celebration Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextArea::make('description')->nullable(),
                TextInput::make('weekday_price')->numeric()->required(),
                TextInput::make('weekend_price')->numeric()->required(),
                TextInput::make('min_children')->numeric()->required(),
                TextInput::make('duration_hours')->numeric()->required(),
                Forms\Components\Grid::make()
                    ->schema([
                        TextInput::make('cashback_percentage')->numeric()->maxValue(100)->default(0),
                        TextInput::make('bonus_playground_visit')->required(),
                        TextInput::make('order')
                            ->numeric()
                            ->label('Order')
                            ->default(1),
                    ])
                    ->columns(3),


                Forms\Components\Repeater::make('features')
                    ->relationship('features')
                    ->schema([
                        TextInput::make('name')->required(),
                        Forms\Components\Select::make('status')->options([
                            'locked'     => 'Locked',
                            'accessible' => 'Accessible',
                        ])
                            ->default('locked')
                            ->required()
                            ->label('Feature Status'),
                        TextInput::make('order')
                            ->numeric()
                            ->label('Order')
                            ->default(1),
                    ])
                    ->minItems(1)
                    ->addActionLabel('Add Feature'),

                Forms\Components\Repeater::make('timelines')
                    ->relationship('timelines')
                    ->schema([
                        TextInput::make('title')->required(),
                        TextInput::make('duration')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->suffix('min')
                            ->required(),
                        TextInput::make('order')
                            ->numeric()
                            ->label('Order')
                            ->default(1),
                    ])->addActionLabel('Add timeline'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('weekday_price')->sortable(),
                TextColumn::make('weekend_price')->sortable(),
                TextColumn::make('min_children')->sortable(),
                TextColumn::make('duration_hours')->sortable(),
                TextColumn::make('cashback_percentage')->sortable(),
                TextColumn::make('bonus_playground_visit')->sortable(),

            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit'   => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
