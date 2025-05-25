<?php

namespace App\Filament\Resources\FamilyResource\RelationManagers;

use App\Enums\GenderEnum;
use App\Filament\Resources\CelebrationResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $recordTitleAttribute = 'first_name';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                    ->collection('child_avatars')
                    ->circular()
                    ->label('Avatar'),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof GenderEnum) {
                            return $state->value;
                        }

                        return $state;
                    })->color(function ($state) {
                        $value = $state instanceof GenderEnum ? $state->value : $state;

                        return match ($value) {
                            'male' => 'success',
                            'female' => 'danger',
                            default => 'warning',
                        };
                    }),
                Tables\Columns\TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('celebrations_count')
                    ->counts('celebrations')
                    ->label('Celebrations')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Action::make('editAvatar')
                    ->label('Edit Avatar')
                    ->icon('heroicon-o-camera')
                    ->modalHeading(fn($record) => "Update Avatar for $record->first_name $record->last_name")
                    ->form([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                            ->collection('child_avatars')
                            ->disk('s3')
                            ->label('Avatar')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                    ])->action(function (array $data, $record): void {
                        if (isset($data['avatar'])) {
                            $record->clearMediaCollection('child_avatars');
                            $record->addMedia($data['avatar'])->toMediaCollection('child_avatars');
                        }
                    }),
                Action::make('viewCelebrations')
                    ->label('View Celebrations')
                    ->icon('heroicon-o-cake')
                    ->url(fn(Model $record) => CelebrationResource::getUrl() . '?tableFilters[child][value]=' . $record->id)
                    ->color('primary'),
            ])
            ->bulkActions([
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                    ->collection('child_avatars')
                    ->disk('s3')
                    ->label('Avatar')
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('300')
                    ->imageResizeTargetHeight('300')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                Forms\Components\TextInput::make('first_name')
                    ->label('First Name')
                    ->disabled(),
                Forms\Components\TextInput::make('last_name')
                    ->label('Last Name')
                    ->disabled(),
                Forms\Components\TextInput::make('gender')
                    ->label('Gender')
                    ->disabled(),
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Date of Birth')
                    ->disabled(),

            ]);
    }
}
