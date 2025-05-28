<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Concerns\HasUserSearchForm;
use App\Models\Celebration;
use App\Models\Child;
use App\Models\Product;
use App\Models\User;
use App\Services\PassService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Throwable;

class CelebrationChildrenPage extends Page implements HasForms, HasInfolists, HasTable
{
    use InteractsWithForms;
    use InteractsWithInfolists;
    use InteractsWithTable;
    use HasGlobalUserSearch;
    use HasUserSearchForm;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Celebration Children';

    protected static ?string $slug = 'celebration-children';

    protected static ?string $cluster = Cashier::class;

    protected static string $view = 'filament.clusters.cashier.pages.celebration-children';

    protected static ?int $navigationSort = 3;

    #[Url]
    public ?string $celebrationId = null;

    public ?Celebration $celebration = null;

    public $selectedCelebration = null;

    public ?array $data = [];
    public $guestUserId = null;
    public ?User $guestUser = null;
    public $childId = null;

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('manageCelebrationChildren');
    }

    public function mount(): void
    {
        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }

        $this->mountHasUserSearchForm();

        if (empty($this->celebrationId) && $this->selectedUser && $this->selectedUser->family) {
            $this->celebration = Celebration::where('family_id', $this->selectedUser->family->id)
                ->latest()
                ->first();
            $this->celebrationId = $this->celebration?->id;
        } elseif (empty($this->celebrationId)) {
            $this->celebration = Celebration::latest()->first();
            $this->celebrationId = $this->celebration?->id;
        } else {
            $this->celebration = Celebration::find($this->celebrationId);
        }

        $this->data = [
            'selectedCelebration' => $this->celebrationId,
        ];

        $this->form->fill($this->data);
    }

    public function refreshForm(): void
    {
        Log::info('Refreshing celebration children form', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser'        => (bool)$this->selectedUser,
        ]);

        if ($this->selectedUserId) {
            $this->selectedUser = User::with([
                'profile',
                'family',
                'family.children'
            ])->find($this->selectedUserId);

            if ($this->selectedUser && $this->selectedUser->family) {
                $this->celebration = Celebration::where('family_id', $this->selectedUser->family->id)
                    ->latest()
                    ->first();
                $this->celebrationId = $this->celebration?->id;
                $this->selectedCelebration = $this->celebrationId;
            }
        }

        $this->data = [
            'selectedCelebration' => $this->celebrationId,
        ];

        $this->refreshUserSearchForm();
        $this->form->fill($this->data);
    }

    public function celebrationInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->celebration)
            ->schema([
                TextEntry::make('child.first_name')
                    ->label('Child'),
                TextEntry::make('theme.name')
                    ->label('Theme'),
                TextEntry::make('celebration_date')
                    ->dateTime()
                    ->label('Date'),
                TextEntry::make('children_count')
                    ->numeric()
                    ->label('Children Count'),
                TextEntry::make('parents_count')
                    ->numeric()
                    ->label('Parents Count'),
            ])
            ->columns(5);
    }

    public function searchGuest(): void
    {
        if (empty($this->guestUserId)) {
            return;
        }

        $this->guestUser = User::with(['profile', 'family.children'])->find($this->guestUserId);

        if (!$this->guestUser) {
            Notification::make()
                ->title('No user found with the provided information')
                ->warning()
                ->send();
            return;
        }

        $this->childId = null;

        Notification::make()
            ->title('User found')
            ->success()
            ->send();
    }

    /**
     * @throws Throwable
     */
    public function addChild(): void
    {
        if (!$this->childId || !$this->celebration) {
            Notification::make()
                ->title('Please select a child')
                ->warning()
                ->send();
            return;
        }

        $exists = DB::table('celebration_child')
            ->where('celebration_id', $this->celebration->id)
            ->where('child_id', $this->childId)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Child already invited to this celebration')
                ->warning()
                ->send();
            return;
        }
        $this->celebration->invitations()->attach($this->childId, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $child = Child::find($this->childId);
        if ($child) {
            $this->createFreePlaygroundTicket($child);
        }

        $this->childId = null;

        Notification::make()
            ->title('Child added to celebration')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if (!$this->celebration) {
                    return Child::query()->whereNull('id');
                }

                return Child::query()
                    ->select('children.*')
                    ->join('celebration_child', 'children.id', '=', 'celebration_child.child_id')
                    ->where('celebration_child.celebration_id', $this->celebration->id);
            })
            ->recordTitle(fn(Child $record): string => "{$record->first_name}")
            ->columns([
                TextColumn::make('first_name')
                    ->label('Child Name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable(),
                TextColumn::make('gender')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->value ?? $state)
                    ->color(fn($state) => match ($state->value ?? $state) {
                        'male' => 'success',
                        'female' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('family.name')
                    ->label('Family')
                    ->searchable(),
                TextColumn::make('pivot_created_at')
                    ->label('Added At')
                    ->dateTime()
                    ->getStateUsing(function (Child $record) {
                        if ($this->celebration) {
                            $pivot = DB::table('celebration_child')
                                ->where('celebration_id', $this->celebration->id)
                                ->where('child_id', $record->id)
                                ->first();

                            return $pivot ? $pivot->created_at : null;
                        }

                        return null;
                    }),

            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Action::make('printTicket')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->button()
                    ->action(function (Child $record) {
                        $pass = DB::table('passes')
                            ->where('child_id', $record->id)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($pass) {
                            $printUrl = route('filament.admin.pass.print', ['serial' => $pass->serial]);

                            $this->js("window.open('$printUrl', '_blank')");

                            Notification::make()
                                ->title('Print page opened')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No ticket found')
                                ->body('Please create a ticket first using "Recreate Ticket" button')
                                ->warning()
                                ->send();
                        }
                    }),
                Action::make('remove')
                    ->label('Remove')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (Child $record) {
                        if ($this->celebration) {
                            $this->celebration->invitations()->detach($record->id);

                            Notification::make()
                                ->title('Child removed from celebration')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No children invited yet')
            ->emptyStateDescription('Add children to this celebration by using the form above')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    /**
     * Create a free playground ticket for a child when they're added to a celebration
     *
     * @param Child $child
     * @return void
     * @throws Throwable
     */
    protected function createFreePlaygroundTicket(Child $child): void
    {
        try {
            DB::beginTransaction();

            $celebration = $this->celebration;

            Log::info('Creating playground ticket for child', [
                'child_id'       => $child->id,
                'child_name'     => $child->full_name ?? "{$child->first_name} {$child->last_name}",
                'celebration_id' => $celebration->id,
            ]);

            $availableProducts = Product::available()->get();

            $playgroundProduct = $availableProducts->first(function ($product) {
                return stripos($product->name, 'playground') !== false;
            });

            if (!$playgroundProduct) {
                $playgroundProduct = $availableProducts->first(function ($product) {
                    return stripos($product->name, 'play') !== false ||
                        stripos($product->name, 'entry') !== false;
                });
            }

            if (!$playgroundProduct && $availableProducts->isNotEmpty()) {
                $playgroundProduct = $availableProducts->sortBy(function ($product) {
                    return $product->getFinalPrice(now());
                })->first();
            }

            if (!$playgroundProduct) {
                Notification::make()
                    ->title('Playground ticket creation failed')
                    ->body('Could not find any available products')
                    ->danger()
                    ->send();
                DB::rollBack();
                return;
            }

            $user = $child->family->users()->first();
            if (!$user) {
                Notification::make()
                    ->title('Playground ticket creation failed')
                    ->body('Could not find a user associated with the child\'s family')
                    ->danger()
                    ->send();
                DB::rollBack();
                return;
            }

            $pass = app(PassService::class)->purchase(
                user: $user,
                child: $child,
                product: $playgroundProduct,
                isFree: true,
                activationDate: $celebration->celebration_date ?? Carbon::now(),
                meta: [
                    'celebration_id' => $celebration->id,
                    'auto_generated' => true,
                    'description'    => 'Free ticket for celebration attendance'
                ]
            );

            app(PassService::class)->generateQRCode($pass->serial, true);

            $printUrl = route('filament.admin.pass.print', ['serial' => $pass->serial]);

            DB::commit();

            Notification::make()
                ->title('Free ticket created')
                ->body("A free {$playgroundProduct->name} ticket has been created for {$child->first_name}")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('print')
                        ->label('Print Ticket')
                        ->url($printUrl)
                        ->openUrlInNewTab()
                        ->button(),
                ])
                ->send();

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error creating free ticket', [
                'exception'      => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
                'child_id'       => $child->id ?? null,
                'celebration_id' => $this->celebration->id ?? null,
            ]);

            Notification::make()
                ->title('Ticket creation failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }


    public function getTitle(): string|Htmlable
    {
        if ($this->celebration) {
            $childName = $this->celebration->child->first_name ?? 'Unknown';
            return "$childName's Celebration Children";
        }

        return parent::getTitle();
    }

    public function clearSelectedUser(): void
    {
        $this->selectUser(null);
        $this->refreshUserSearchForm();

        $this->celebrationId = null;
        $this->celebration = null;

        $this->data = [
            'selectedCelebration' => null,
        ];

        $this->form->fill($this->data);
    }

    public function clearGuestUser(): void
    {
        $this->guestUser = null;
        $this->guestUserId = null;
        $this->childId = null;
    }

    protected function getForms(): array
    {
        return [
            'form'            => $this->makeForm()
                ->schema($this->getFormSchema())
                ->statePath('data'),
            'guestSearchForm' => $this->makeForm()
                ->schema($this->getGuestSearchFormSchema()),
            'addChildForm'    => $this->makeForm()
                ->schema($this->getAddChildFormSchema()),
        ];
    }

    protected function getFormSchema(): array
    {
        $schema = [];

        if (!$this->selectedUser) {
            $schema[] = $this->getUserSearchField()
                ->columnSpanFull();
        }

        if ($this->selectedUser) {
            $query = Celebration::orderByDesc('celebration_date');

            if ($this->selectedUser->family) {
                $query->where('family_id', $this->selectedUser->family->id);
            }

            $schema[] = Select::make('selectedCelebration')
                ->label('Select Celebration')
                ->options($query->whereTodayOrAfter('celebration_date')->get()
                    ->mapWithKeys(function ($celebration) {
                        $childName = $celebration->child?->first_name ?? 'Unknown';
                        $date = $celebration->celebration_date?->format('d M Y') ?? 'No date';
                        return [$celebration->id => "$childName's celebration - $date"];
                    }))
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->celebrationId = $state;
                    $this->celebration = Celebration::find($state);
                })
                ->required();
        }

        return $schema;
    }

    protected function getGuestSearchFormSchema(): array
    {
        return [
            Select::make('guestUserId')
                ->label('Search for a Guest User')
                ->placeholder('Search by name, email, or phone')
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    return User::where(function ($query) use ($search) {
                        $query->where('email', 'like', "%{$search}%")
                            ->orWhereHas('profile', function ($q) use ($search) {
                                $q->where('phone_number', 'like', "%{$search}%")
                                    ->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function (User $user) {
                            $name = $user->profile ? "{$user->profile->first_name} {$user->profile->last_name}" : $user->email;
                            $phone = $user->profile?->phone_number ? " ({$user->profile->phone_number})" : '';
                            return [$user->id => $name . $phone];
                        })
                        ->toArray();
                })
                ->getOptionLabelUsing(fn($value): ?string => User::find($value)?->full_name)
                ->helperText('Select a user to add their child to the celebration'),
        ];
    }

    protected function getAddChildFormSchema(): array
    {
        return [
            Select::make('childId')
                ->label('Select Child')
                ->options(function () {
                    if (!$this->guestUser || !$this->guestUser->family) {
                        return [];
                    }

                    $invitedChildIds = [];
                    if ($this->celebration) {
                        $invitedChildIds = DB::table('celebration_child')
                            ->where('celebration_id', $this->celebration->id)
                            ->pluck('child_id')
                            ->toArray();
                    }

                    // Get all children from this family except those already invited
                    return Child::where('family_id', $this->guestUser->family->id)
                        ->whereNotIn('id', $invitedChildIds)
                        ->get()
                        ->pluck('first_name', 'id');
                })
                ->live()
                ->searchable()
                ->required(),
        ];
    }

    protected function getListeners(): array
    {
        return [
            'user-selected' => 'refreshForm',
        ];
    }
}
