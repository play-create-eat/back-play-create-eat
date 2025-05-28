<?php

namespace App\Filament\Clusters\Cashier\Resources;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Resources\PassResource\Pages;
use App\Models\Child;
use App\Models\Pass;
use App\Models\User;
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
use Session;

class PassResource extends Resource
{
    protected static ?string $model = Pass::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $cluster = Cashier::class;

    protected static ?int $navigationSort = 5;

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
        $selectedUserId = Session::get('cashier.selected_user_id');
        $selectedUser = null;

        $query = Pass::query();

        if ($selectedUserId) {
            $selectedUser = User::with(['family.children'])->find($selectedUserId);
        }

        return $table
            ->recordUrl(function (Pass $record) {
                return null;
//                return self::getUrl('view', ['record' => $record]);
            })
            ->modifyQueryUsing(function (Builder $query) use ($selectedUser) {
                $query->with(['user.profile', 'user.family', 'children']);

                if ($selectedUser?->family?->children->isNotEmpty()) {
                    $childrenIds = $selectedUser->family->children->pluck('id')->toArray();

                    $query->where('user_id', $selectedUser->id)
                        ->whereIn('child_id', $childrenIds);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('serial')
                    ->searchable()
                    ->getStateUsing(fn(Pass $record): HtmlString => new HtmlString("<span class='text-xs font-mono'>{$record->serial}</span>"))
                ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('selectedUserFamilyChildren')
                ->label('Children')
                ->options(function () use ($selectedUser) {
                    if (!$selectedUser || !$selectedUser->family) {
                        return [];
                    }

                    $passChildrenIds = Pass::where('user_id', $selectedUser->id)
                        ->pluck('child_id')
                        ->unique()
                        ->toArray();


                    return $selectedUser->family->children
                        ->filter(function (Child $child) use ($passChildrenIds) {
                            return in_array($child->id, $passChildrenIds);
                        })
                        ->mapWithKeys(function (Child $child) {
                            return [$child->id => $child->full_name];
                        })
                        ->toArray();
                })
                ->attribute('child_id')
                ->multiple()
                ->preload()
                ->visible(fn() => $selectedUser?->family)
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
