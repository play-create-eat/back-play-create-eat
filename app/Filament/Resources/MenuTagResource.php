<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuTagResource\Pages;
use App\Filament\Resources\MenuTagResource\RelationManagers;
use App\Models\MenuTag;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuTagResource extends Resource
{
    protected static ?string $model = MenuTag::class;

    protected static ?string $navigationGroup = 'Menu Management';

    protected static ?string $navigationLabel = 'Tags';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->label('Tag Name'),
                ColorPicker::make('color')->required()->label('Color Code'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable()->label('Tag Name'),
                TextColumn::make('color')->sortable()->searchable()->label('Color Code'),
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
            'index'  => Pages\ListMenuTags::route('/'),
            'create' => Pages\CreateMenuTag::route('/create'),
            'edit'   => Pages\EditMenuTag::route('/{record}/edit'),
        ];
    }
}
