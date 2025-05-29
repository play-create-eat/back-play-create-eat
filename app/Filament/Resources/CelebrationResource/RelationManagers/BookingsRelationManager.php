<?php

namespace App\Filament\Resources\CelebrationResource\RelationManagers;

use App\Models\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table as FilamentTable;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';
    protected static ?string $title = 'Table Bookings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('child_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('children_count')
                    ->required()
                    ->numeric()
                    ->minValue(1),

                Forms\Components\DateTimePicker::make('start_time')
                    ->required()
                    ->label('Event Start Time'),

                Forms\Components\DateTimePicker::make('end_time')
                    ->required()
                    ->label('Event End Time'),

                Forms\Components\DateTimePicker::make('setup_start_time')
                    ->required()
                    ->label('Setup Start Time'),

                Forms\Components\DateTimePicker::make('cleanup_end_time')
                    ->required()
                    ->label('Cleanup End Time'),

                Forms\Components\CheckboxList::make('tables')
                    ->relationship('tables', 'name')
                    ->options(Table::where('is_active', true)->pluck('name', 'id'))
                    ->columns(2)
                    ->required()
                    ->label('Select Tables'),

                Forms\Components\Textarea::make('special_requests')
                    ->columnSpanFull()
                    ->rows(3),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }

    public function table(FilamentTable $table): FilamentTable
    {
        return $table
            ->recordTitleAttribute('child_name')
            ->columns([
                Tables\Columns\TextColumn::make('child_name')
                    ->label('Child Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tables')
                    ->label('Tables & Capacity')
                    ->formatStateUsing(function ($record) {
                        $tables = $record->tables;
                        if ($tables->isEmpty()) {
                            return 'No tables assigned';
                        }

                        return $tables->map(function($table) {
                            return $table->name . ' (Cap: ' . $table->capacity . ')';
                        })->join(', ');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('children_count')
                    ->label('Children')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Event Time')
                    ->formatStateUsing(function ($record) {
                        return $record->start_time->format('M j, Y') . '<br>' .
                               $record->start_time->format('H:i') . ' - ' . $record->end_time->format('H:i');
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('setup_start_time')
                    ->label('Setup & Cleanup')
                    ->formatStateUsing(function ($record) {
                        return 'Setup: ' . $record->setup_start_time->format('H:i') . '<br>' .
                               'Cleanup: ' . $record->cleanup_end_time->format('H:i');
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('tables')
                    ->relationship('tables', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = $this->ownerRecord->user_id;
                        $data['celebration_id'] = $this->ownerRecord->id;
                        $data['package_id'] = $this->ownerRecord->package_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_time', 'asc');
    }
}
