<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThemeResource\Pages;
use App\Models\Theme;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static ?string $navigationGroup = 'Celebration Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextArea::make('description')->nullable(),
                Select::make('type')->options([
                    'Birthday Party' => 'Birthday Party'
                ])
                    ->default('Birthday Party')
                    ->required(),
                TextInput::make('category')->required(),
                Grid::make()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('main_image')
                            ->collection('main_images')
                            ->image()
                            ->disk('s3')
                            ->label('Main Image')
                            ->helperText('Only one main image allowed')
                            ->maxFiles(1)
                            ->customProperties([
                                'main' => true,
                            ])
                            ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file) {
                                if (!$file) return;

                                $mediaClass = config('media-library.media_model', Media::class);
                                $mediaClass::findByUuid($file)?->delete();
                            })
                            ->columnSpan(1),
                        SpatieMediaLibraryFileUpload::make('images')
                            ->collection('theme_images')
                            ->image()
                            ->disk('s3')
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(5)
                            ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file) {
                                if (!$file) return;

                                $mediaClass = config('media-library.media_model', Media::class);
                                $mediaClass::findByUuid($file)?->delete();
                            })->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('category')->searchable()->sortable(),
                TextColumn::make('description')->limit(50),
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
            'index'  => Pages\ListThemes::route('/'),
            'create' => Pages\CreateTheme::route('/create'),
            'edit'   => Pages\EditTheme::route('/{record}/edit'),
        ];
    }
}
