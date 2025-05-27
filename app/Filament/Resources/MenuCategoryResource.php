<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuCategoryResource\Pages;
use App\Models\MenuCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuCategoryResource extends Resource
{
    protected static ?string $model = MenuCategory::class;
    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?string $pluralLabel = 'Menu Categories';
    protected static ?string $slug = 'menu-categories';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('manageMenu');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                Select::make('menu_type_id')
                    ->label('Menu Type')
                    ->relationship('menuType', 'title')
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('menuType.title')
                    ->label('Menu Type')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([

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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMenuCategories::route('/'),
            'create' => Pages\CreateMenuCategory::route('/create'),
            'edit'   => Pages\EditMenuCategory::route('/{record}/edit'),
        ];
    }
}
