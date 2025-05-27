<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModifierGroupResource\Pages;
use App\Models\ModifierGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModifierGroupResource extends Resource
{
    protected static ?string $model = ModifierGroup::class;

    protected static ?string $navigationGroup = 'Menu Management';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('manageMenu');
    }

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
                Select::make('menuItem')
                    ->relationship('menuItem', 'name')
                    ->multiple()
                    ->preload(),

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
            'index'  => Pages\ListModifierGroups::route('/'),
            'create' => Pages\CreateModifierGroup::route('/create'),
            'edit'   => Pages\EditModifierGroup::route('/{record}/edit'),
        ];
    }
}
