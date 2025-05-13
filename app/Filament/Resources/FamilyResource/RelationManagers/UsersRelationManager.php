<?php

namespace App\Filament\Resources\FamilyResource\RelationManagers;

use App\Filament\Resources\UserResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name'),
                Tables\Columns\TextColumn::make('profile.phone_number')
                    ->label('Phone Number'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => UserResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
            ]);
    }
}
