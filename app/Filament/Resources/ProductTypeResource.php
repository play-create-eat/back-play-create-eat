<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTypeResource\Pages;
use App\Models\Product;
use App\Models\ProductType;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;


class ProductTypeResource extends Resource
{
    protected static ?string $model = ProductType::class;

    protected static ?string $navigationGroup = 'Product Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Details')
                    ->schema([
                        TextInput::make('name')
                            ->autocomplete(false)
                            ->minLength(3)
                            ->maxLength(255)
                            ->required(),
                        Textarea::make('description'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(function (ProductType $record) {
                if ($record->trashed()) {
                    return null;
                }
                return self::getUrl('edit', ['record' => $record]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn(ProductType $record) => $record->trashed()),
                DeleteAction::make(),
                Action::make('restore')
                    ->requiresConfirmation()
                    ->action(fn(ProductType $record) => $record->restore())
                    ->hidden(fn(ProductType $record) => !$record->trashed())
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => $table->getLivewire()->activeTab === 'archive'),
                    BulkAction::make('restore')
                        ->requiresConfirmation()
                        ->action(fn(Collection $records) => $records->each->restore())
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->hidden(fn() => $table->getLivewire()->activeTab !== 'archive'),
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
            'index' => Pages\ListProductTypes::route('/'),
            'create' => Pages\CreateProductType::route('/create'),
            'edit' => Pages\EditProductType::route('/{record}/edit'),
        ];
    }
}
