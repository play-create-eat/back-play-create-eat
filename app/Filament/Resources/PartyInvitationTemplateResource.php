<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartyInvitationTemplateResource\Pages;
use App\Filament\Resources\PartyInvitationTemplateResource\RelationManagers;
use App\Models\PartyInvitationTemplate;
use Exception;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class PartyInvitationTemplateResource extends Resource
{
    protected static ?string $model = PartyInvitationTemplate::class;

    protected static ?string $navigationGroup = 'Celebration Management';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewPartyInvitationTemplates');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ColorPicker::make('text_color')
                    ->label('Text Color')
                    ->required()
                    ->default('#FFFFFF'),
                Fieldset::make('Images')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('party_invitation_previews')
                            ->collection('party_invitation_previews')
                            ->image()
                            ->required()
                            ->maxSize(10240)
                            ->label('Preview Image'),
                        SpatieMediaLibraryFileUpload::make('party_invitation_templates')
                            ->collection('party_invitation_templates')
                            ->image()
                            ->required()
                            ->maxSize(10240)
                            ->label('Template Image'),
                    ]),

            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\ColorColumn::make('text_color')
                    ->label('Text Color'),
                Tables\Columns\ImageColumn::make('preview_url')
                    ->label('Preview')
                    ->square(),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Template')
                    ->square(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        if ($record->file_path) {
                            Storage::cloud()->delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->action(fn(Collection $records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion()
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation(),
                Tables\Actions\BulkAction::make('deactivate')
                    ->action(fn(Collection $records) => $records->each->update(['is_active' => false]))
                    ->deselectRecordsAfterCompletion()
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation(),
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        foreach ($records as $record) {
                            if ($record->file_path) {
                                Storage::cloud()->delete($record->file_path);
                            }
                        }
                    }),
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
            'index'  => Pages\ListPartyInvitationTemplates::route('/'),
            'create' => Pages\CreatePartyInvitationTemplate::route('/create'),
            'edit'   => Pages\EditPartyInvitationTemplate::route('/{record}/edit'),
        ];
    }
}
