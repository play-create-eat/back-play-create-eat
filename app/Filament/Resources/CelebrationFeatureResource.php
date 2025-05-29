<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CelebrationFeatureResource\Pages;
use App\Models\CelebrationFeature;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CelebrationFeatureResource extends Resource
{
    protected static ?string $model = CelebrationFeature::class;

    protected static ?string $navigationGroup = 'Celebration Management';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewCelebrationFeatures');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3),
                TextInput::make('price')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('F j, Y')
                    ->sortable(),
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
            'index'  => Pages\ListCelebrationFeatures::route('/'),
            'create' => Pages\CreateCelebrationFeature::route('/create'),
            'edit'   => Pages\EditCelebrationFeature::route('/{record}/edit'),
        ];
    }
}
