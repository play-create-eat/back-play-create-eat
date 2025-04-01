<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModifierGroupResource\Pages;
use App\Filament\Resources\ModifierGroupResource\RelationManagers;
use App\Models\ModifierGroup;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModifierGroupResource extends Resource
{
    protected static ?string $model = ModifierGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->label('Modifier Group Name'),
                TextInput::make('min_amount')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->label('Minimum Amount'),
                TextInput::make('max_amount')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->label('Maximum Amount'),
                Repeater::make('options')
                    ->relationship('options')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('Option Name'),
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->label('Option Price'),
                    ])
                    ->minItems(1)
                    ->addActionLabel('Add Option'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->sortable()->searchable()->label('Modifier Group Name'),
                TextColumn::make('min_amount')->sortable()->searchable()->label('Minimum Amount'),
                TextColumn::make('max_amount')->sortable()->searchable()->label('Maximum Amount'),
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
            'index' => Pages\ListModifierGroups::route('/'),
            'create' => Pages\CreateModifierGroup::route('/create'),
            'edit' => Pages\EditModifierGroup::route('/{record}/edit'),
        ];
    }
}
