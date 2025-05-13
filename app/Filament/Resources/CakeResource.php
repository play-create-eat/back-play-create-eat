<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CakeResource\Pages;
use App\Models\Cake;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CakeResource extends Resource
{
    protected static ?string $model = Cake::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('type')->required(),
                TextInput::make('price_per_kg')->numeric()->required(),
                SpatieMediaLibraryFileUpload::make('images')
                    ->collection('cake_images')
                    ->image()
                    ->disk('s3')
                    ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file) {
                        if (!$file) return;

                        $mediaClass = config('media-library.media_model', Media::class);
                        $mediaClass::findByUuid($file)?->delete();
                    })->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('price_per_kg'),
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
            'index'  => Pages\ListCakes::route('/'),
            'create' => Pages\CreateCake::route('/create'),
            'edit'   => Pages\EditCake::route('/{record}/edit'),
        ];
    }
}
