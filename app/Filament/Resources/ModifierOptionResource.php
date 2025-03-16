<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModifierOptionResource\Pages;
use App\Filament\Resources\ModifierOptionResource\RelationManagers;
use App\Models\ModifierOption;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModifierOptionResource extends Resource
{
    protected static ?string $model = ModifierOption::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Modifier Option Details')
                    ->schema([
                        Select::make('modifier_group_id')
                            ->relationship('modifierGroup', 'title')
                            ->required()
                            ->label('Modifier Group'),
                        TextInput::make('name')
                            ->required()
                            ->label('Option Name'),
                        TextInput::make('price')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->label('Option Price'),
                    ]),
                Section::make('Nutrition Info')
                    ->description('Enter the nutrition information for the option.')
                    ->schema([
                        TextInput::make('nutrition_info.calories')
                            ->numeric()
                            ->minValue(0)
                            ->label('Calories'),
                        TextInput::make('nutrition_info.protein')
                            ->numeric()
                            ->minValue(0)
                            ->label('Protein'),
                        TextInput::make('nutrition_info.fat')
                            ->numeric()
                            ->minValue(0)
                            ->label('Fat'),
                        TextInput::make('nutrition_info.carbohydrates')
                            ->numeric()
                            ->minValue(0)
                            ->label('Carbohydrates'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->label('ID'),
                TextColumn::make('modifierGroup.title')->sortable()->searchable()->label('Modifier Group'),
                TextColumn::make('name')->sortable()->searchable()->label('Option Name'),
                TextColumn::make('price')->sortable()->searchable()->label('Option Price'),
            ])
            ->filters([
                //
            ])
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
            'index' => Pages\ListModifierOptions::route('/'),
            'create' => Pages\CreateModifierOption::route('/create'),
            'edit' => Pages\EditModifierOption::route('/{record}/edit'),
        ];
    }
}
