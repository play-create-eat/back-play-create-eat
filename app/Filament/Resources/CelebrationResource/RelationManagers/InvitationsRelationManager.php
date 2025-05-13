<?php

namespace App\Filament\Resources\CelebrationResource\RelationManagers;

use App\Models\Child;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'invitations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('child_id')
                    ->label('Child')
                    ->options(Child::all()->pluck('full_name', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Child $record): string => $record->full_name)
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('family.name')
                    ->label('Family'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                ->preloadRecordSelect()
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                ]),
            ]);
    }
}
