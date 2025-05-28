<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SurveyResource\Pages;
use App\Models\Survey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Feedback';

    protected static ?string $navigationLabel = 'Survey Responses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Play Section')
                    ->schema([
                        Forms\Components\Toggle::make('play_interesting')
                            ->label('Interesting')
                            ->disabled(),
                        Forms\Components\Toggle::make('play_safe')
                            ->label('Safe')
                            ->disabled(),
                        Forms\Components\Toggle::make('play_staff_friendly')
                            ->label('Staff Friendly')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Create Section')
                    ->schema([
                        Forms\Components\Toggle::make('create_activities_interesting')
                            ->label('Activities Interesting')
                            ->disabled(),
                        Forms\Components\Toggle::make('create_staff_friendly')
                            ->label('Staff Friendly')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Eat Section')
                    ->schema([
                        Forms\Components\TextInput::make('eat_liked_food')
                            ->label('Liked Food')
                            ->disabled(),
                        Forms\Components\TextInput::make('eat_liked_drinks')
                            ->label('Liked Drinks')
                            ->disabled(),
                        Forms\Components\TextInput::make('eat_liked_pastry')
                            ->label('Liked Pastry')
                            ->disabled(),
                        Forms\Components\TextInput::make('eat_team_friendly')
                            ->label('Team Friendly')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Conclusion')
                    ->schema([
                        Forms\Components\Textarea::make('conclusion_suggestions')
                            ->label('Suggestions')
                            ->disabled()
                            ->rows(4),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('user_email')
                            ->label('User Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                        Forms\Components\TextInput::make('created_at')
                            ->label('Submitted At')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                IconColumn::make('play_interesting')
                    ->label('Play: Interesting')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('play_safe')
                    ->label('Play: Safe')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('create_activities_interesting')
                    ->label('Create: Activities')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('eat_liked_food')
                    ->label('Food')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yes' => 'success',
                        'no' => 'danger',
                        'cannot_judge' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('eat_liked_drinks')
                    ->label('Drinks')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yes' => 'success',
                        'no' => 'danger',
                        'cannot_judge' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('conclusion_suggestions')
                    ->label('Suggestions')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('eat_liked_food')
                    ->label('Food Rating')
                    ->options([
                        'yes' => 'Yes',
                        'no' => 'No',
                        'cannot_judge' => 'Cannot Judge',
                    ]),
                Tables\Filters\SelectFilter::make('eat_liked_drinks')
                    ->label('Drinks Rating')
                    ->options([
                        'yes' => 'Yes',
                        'no' => 'No',
                        'cannot_judge' => 'Cannot Judge',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSurveys::route('/'),
            'view' => Pages\ViewSurvey::route('/{record}'),
        ];
    }
} 