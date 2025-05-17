<?php

namespace App\Filament\Clusters\Cashier\Resources;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Resources\PassResource\Pages;
use App\Models\Child;
use App\Models\Pass;
use Carbon\CarbonInterval;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class PassResource extends Resource
{
    protected static ?string $model = Pass::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $cluster = Cashier::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('serial')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('remaining_time')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_extendable')
                    ->required(),
                Forms\Components\TextInput::make('child_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('transfer_id')
                    ->relationship('transfer', 'id')
                    ->required(),
                Forms\Components\DateTimePicker::make('entered_at'),
                Forms\Components\DateTimePicker::make('exited_at'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Forms\Components\DatePicker::make('activation_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(function (Pass $record) {
                return null;
//                return self::getUrl('view', ['record' => $record]);
            })
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['user.profile', 'user.family', 'children']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('serial')
                    ->searchable()
                    ->getStateUsing(fn(Pass $record): HtmlString => new HtmlString("<span class='text-xs font-mono'>{$record->serial}</span>")),
                Tables\Columns\TextColumn::make('user')
                    ->label('Client')
                    ->getStateUsing(function (Pass $record) {
                        return "{$record->user?->full_name} ({$record->user?->family?->name})";
                    })
                    ->description(function (Pass $record): HtmlString {
                        return new HtmlString("<span class='text-xs font-mono'>{$record->user?->profile?->phone_number}</span>");
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('children')
                    ->label('Children')
                    ->getStateUsing(fn(Pass $record): string => $record->children?->full_name)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('remaining_time')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn(string $state) => CarbonInterval::minutes($state)->cascade()->forHumans([
                        'join' => true,
                        'parts' => 3,
                    ])),
                Tables\Columns\IconColumn::make('is_extendable')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('entered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('exited_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expires_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('activation_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('family')
                    ->relationship('children.family', 'name')
                    ->searchable()
                    ->preload()
                    ->modifyFormFieldUsing(function (Select $field) {
                        $field
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set, HasTable $livewire) => $livewire->removeTableFilter('children'));
                    }),
                SelectFilter::make('children')
                    ->relationship(
                        name: 'children',
                        titleAttribute: 'first_name',
                        modifyQueryUsing: function (
                            Builder  $query,
                            HasTable $livewire
                        ) {
                            $query->when(
                                value: $livewire->getTableFilterState('family'),
                                callback: fn($q, $familyId) => $q->where('family_id', $familyId)
                            );
                        }
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn(Child $record): string => "{$record->first_name} {$record->last_name}"
                    )
                    ->searchable()
                    ->multiple()
                    ->preload(),
            ])
            ->deferFilters()
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->url(fn(Pass $record) => route('filament.admin.pass.print', ['serial' => $record->serial]))
                    ->openUrlInNewTab()
                    ->button(),
            ], position: ActionsPosition::BeforeColumns);
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
            'index' => Pages\ListPasses::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canBulkDelete(): bool
    {
        return false;
    }
}
