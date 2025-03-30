<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvitationTemplateResource\Pages;
use App\Filament\Resources\InvitationTemplateResource\RelationManagers;
use App\Models\InvitationTemplate;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvitationTemplateResource extends Resource
{
    protected static ?string $model = InvitationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('logo_color')
                    ->label('Logo Color')
                    ->options([
                        'white' => 'White',
                        'color' => 'Color',
                    ])
                    ->required(),
                Select::make('decoration_type')
                    ->label('Decoration Type')
                    ->options([
                        'first'  => 'First',
                        'second' => 'Second',
                    ])
                    ->required(),
                ColorPicker::make('background_color')->label('Background Color')->required(),
                ColorPicker::make('text_color')->label('Text Color')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('logo_color')->label('Logo Color'),
                TextColumn::make('decoration_type')->label('Decoration Type'),
                ColorColumn::make('background_color')->label('Background'),
                ColorColumn::make('text_color')->label('Text'),
                TextColumn::make('created_at')->dateTime()->label('Created'),
            ])
            ->filters([
                //
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
            'index'  => Pages\ListInvitationTemplates::route('/'),
            'create' => Pages\CreateInvitationTemplate::route('/create'),
            'edit'   => Pages\EditInvitationTemplate::route('/{record}/edit'),
        ];
    }
}
