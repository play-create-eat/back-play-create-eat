<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use App\Models\Celebration;
use App\Models\Child;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ManageInvitedChildren extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CelebrationResource::class;

    protected static string $view = 'filament.resources.celebration-resource.pages.manage-invited-children';

    public ?Celebration $record = null;

    public function mount(Celebration $record): void
    {
        $this->record = $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addChild')
                ->label('Add Child')
                ->icon('heroicon-o-user-plus')
                ->form([
                    Select::make('child_id')
                        ->label('Child')
                        ->options(
                            Child::whereNotIn('id', $this->record->invitations->pluck('id'))
                                ->get()
                                ->pluck('full_name', 'id')
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();
                        $this->record->invitations()->attach($data['child_id']);

                        $child = Child::find($data['child_id']);
                        Notification::make()
                            ->title('Child added successfully')
                            ->body("{$child->full_name} has been added to the celebration")
                            ->success()
                            ->send();

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Error adding child')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Child::query()
                    ->whereHas('parties', function (Builder $query) {
                        $query->where('celebration_id', $this->record->id);
                    })
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('family.name')
                    ->label('Family')
                    ->searchable()
                    ->placeholder('No Family'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('remove')
                    ->label('Remove')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (Child $record): void {
                        try {
                            DB::beginTransaction();
                            $this->record->invitations()->detach($record->id);

                            Notification::make()
                                ->title('Child removed')
                                ->body("{$record->full_name} has been removed from this celebration")
                                ->success()
                                ->send();

                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('Error removing child')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No children invited yet')
            ->emptyStateDescription('Use the "Add Child" button above to invite children to this celebration.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public function getTitle(): string
    {
        return "Manage Invited Children for {$this->record->child->full_name}'s Celebration";
    }
}
