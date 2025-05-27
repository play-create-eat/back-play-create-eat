<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Filament\Resources\MenuItemResource\RelationManagers;
use App\Models\MenuItem;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?string $navigationLabel = 'Items';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Menu Tags'),
                TextInput::make('name')->required(),
                TextInput::make('price')->numeric()->required(),
                Select::make('menu_category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->label('Category'),
                Textarea::make('description')->nullable(),
                Select::make('modifierGroups')
                    ->relationship('modifierGroups', 'title')
                    ->multiple()
                    ->preload()
                    ->label('Modifier Groups'),
                SpatieMediaLibraryFileUpload::make('menu_item_images')
                    ->collection('menu_item_images')
                    ->image()
                    ->required()
                    ->maxSize(102400),
                Repeater::make('options')
                    ->relationship('options')
                    ->schema([
                        Textarea::make('description')->nullable(),
                        SpatieMediaLibraryFileUpload::make('menu_item_option_image')
                            ->collection('menu_item_option_image')
                            ->image()
                            ->maxSize(102400),
                    ])->addActionLabel('Add Option'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('price')->sortable(),
                TextColumn::make('category.title')->label('Category'),
                TextColumn::make('tags.name')
                    ->label('Tags'),
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
            RelationManagers\ModifierGroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit'   => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
