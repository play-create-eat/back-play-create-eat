<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyActivityResource\Pages;
use App\Models\DailyActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DailyActivityResource extends Resource
{
    protected static ?string $model = DailyActivity::class;

    protected static ?string $navigationLabel = 'Daily Activities';

    protected static ?string $pluralLabel = 'Daily Activities';

    protected static ?string $navigationGroup = 'Activity Management';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Baby&Toddler Sing and Dance'),

                        Textarea::make('description')
                            ->rows(3)
                            ->placeholder('e.g., Clap Snap with Mommy and Daddy'),

                        Select::make('category')
                            ->options([
                                'Baby & Toddler' => 'Baby & Toddler',
                                'Creative Play' => 'Creative Play',
                                'Story Time' => 'Story Time',
                                'Physical Activity' => 'Physical Activity',
                                'Music & Dance' => 'Music & Dance',
                                'Educational' => 'Educational',
                                'Competition' => 'Competition',
                                'Party' => 'Party',
                                'Other' => 'Other',
                            ])
                            ->placeholder('Select a category'),

                        TextInput::make('location')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Disco Room, Create Zone, Play Zone'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Timing & Schedule')
                    ->schema([
                        TimePicker::make('start_time')
                            ->required()
                            ->seconds(false)
                            ->format('H:i'),

                        TimePicker::make('end_time')
                            ->required()
                            ->seconds(false)
                            ->format('H:i')
                            ->after('start_time'),

                        CheckboxList::make('days_of_week')
                            ->options([
                                'Monday' => 'Monday',
                                'Tuesday' => 'Tuesday',
                                'Wednesday' => 'Wednesday',
                                'Thursday' => 'Thursday',
                                'Friday' => 'Friday',
                                'Saturday' => 'Saturday',
                                'Sunday' => 'Sunday',
                            ])
                            ->columns(3)
                            ->label('Days of Week')
                            ->helperText('Leave empty to run all days'),

                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Appearance & Settings')
                    ->schema([
                        ColorPicker::make('color')
                            ->default('#3b82f6')
                            ->helperText('Color for visual representation'),

                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Whether this activity is currently active'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('Color'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'Baby & Toddler',
                        'success' => 'Creative Play',
                        'warning' => 'Story Time',
                        'danger' => 'Physical Activity',
                        'info' => 'Music & Dance',
                        'secondary' => 'Educational',
                        'gray' => 'Other',
                    ]),

                TextColumn::make('formatted_time')
                    ->label('Time')
                    ->sortable(['start_time', 'end_time']),

                TextColumn::make('location')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('days_of_week')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->placeholder('All days'),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'Baby & Toddler' => 'Baby & Toddler',
                        'Creative Play' => 'Creative Play',
                        'Story Time' => 'Story Time',
                        'Physical Activity' => 'Physical Activity',
                        'Music & Dance' => 'Music & Dance',
                        'Educational' => 'Educational',
                        'Competition' => 'Competition',
                        'Party' => 'Party',
                        'Other' => 'Other',
                    ]),

                SelectFilter::make('location')
                    ->options([
                        'Disco Room' => 'Disco Room',
                        'Create Zone' => 'Create Zone',
                        'Play Zone' => 'Play Zone',
                        'All Zone' => 'All Zone',
                        'Reception' => 'Reception',
                    ]),

                Filter::make('is_active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),
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
            ->defaultSort('order', 'asc')
            ->reorderable('order');
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
            'index' => Pages\ListDailyActivities::route('/'),
            'create' => Pages\CreateDailyActivity::route('/create'),
            'edit' => Pages\EditDailyActivity::route('/{record}/edit'),
        ];
    }
}
