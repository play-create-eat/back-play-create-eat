<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuTypeResource\Pages;
use App\Models\MenuType;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MenuTypeResource extends Resource
{
    protected static ?string $model = MenuType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Menu Types';
    protected static ?string $pluralLabel = 'Menu Types';
    protected static ?string $slug = 'menu-types';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')->required()->maxLength(255),
                SpatieMediaLibraryFileUpload::make('images')
                    ->collection('menu_type_images')
                    ->image()
                    ->disk('s3')
                    ->multiple()
                    ->reorderable()
                    ->maxFiles(5)
                    ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file) {
                        if (!$file) return;

                        $mediaClass = config('media-library.media_model', Media::class);
                        $mediaClass::findByUuid($file)?->delete();
                    })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('title')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
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
            'index'  => Pages\ListMenuTypes::route('/'),
            'create' => Pages\CreateMenuType::route('/create'),
            'edit'   => Pages\EditMenuType::route('/{record}/edit'),
        ];
    }
}
