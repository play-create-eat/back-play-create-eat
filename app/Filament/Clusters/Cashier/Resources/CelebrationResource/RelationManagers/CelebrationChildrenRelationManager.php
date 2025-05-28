<?php

namespace App\Filament\Clusters\Cashier\Resources\CelebrationResource\RelationManagers;

use App\Models\Child;
use App\Models\Product;
use App\Models\User;
use App\Services\PassService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CelebrationChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'invitations';

    public ?User $selectedGuestUser = null;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->value ?? $state)
                    ->color(fn($state) => match ($state->value ?? $state) {
                        'male' => 'success',
                        'female' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('family.name')
                    ->label('Family')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Added At'),
            ])
            ->defaultSort('celebration_child.created_at', 'desc')
            ->filters([])
            ->headerActions([
                Tables\Actions\Action::make('addChild')
                    ->label('Add Child')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Grid::make()
                            ->schema([
                                Select::make('guestUserId')
                                    ->label('Search for Guest')
                                    ->placeholder('Search by name, email, or phone')
                                    ->searchable()
                                    ->preload()
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
                                    ->getOptionLabelUsing(fn($value): ?string => User::find($value)?->profile ?
                                        User::find($value)->profile->first_name . ' ' . User::find($value)->profile->last_name :
                                        User::find($value)?->email)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('childId', null);
                                    })
                                    ->required(),

                                Select::make('childId')
                                    ->label('Select Child')
                                    ->options(function (callable $get) {
                                        $userId = $get('guestUserId');
                                        if (!$userId) {
                                            return [];
                                        }

                                        $user = User::with('family.children')->find($userId);
                                        if (!$user || !$user->family) {
                                            return [];
                                        }

                                        $celebration = $this->getOwnerRecord();
                                        $invitedChildIds = DB::table('celebration_child')
                                            ->where('celebration_id', $celebration->id)
                                            ->pluck('child_id')
                                            ->toArray();

                                        return Child::where('family_id', $user->family->id)
                                            ->whereNotIn('id', $invitedChildIds)
                                            ->get()
                                            ->mapWithKeys(function ($child) {
                                                return [$child->id => "{$child->first_name} {$child->last_name}"];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->disabled(fn(callable $get) => !$get('guestUserId')),
                            ])
                    ])
                    ->visible(function () {
                        $celebration = $this->getOwnerRecord();
                        return is_null($celebration->closed_at);
                    })
                    ->action(function (array $data) {
                        $childId = $data['childId'] ?? null;

                        if (!$childId) {
                            Notification::make()
                                ->title('Please select a child')
                                ->warning()
                                ->send();
                            return;
                        }

                        $celebration = $this->getOwnerRecord();

                        $exists = DB::table('celebration_child')
                            ->where('celebration_id', $celebration->id)
                            ->where('child_id', $childId)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Child already invited to this celebration')
                                ->warning()
                                ->send();
                            return;
                        }

                        $celebration->invitations()->attach($childId, [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $child = Child::find($childId);
                        if ($child) {
                            $this->createFreePlaygroundTicket($child);
                        }

                        Notification::make()
                            ->title('Child added to celebration')
                            ->success()
                            ->send();
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('printTicket')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->button()
                    ->visible(function (Child $record) {
                        $celebration = $this->getOwnerRecord();
                        return is_null($celebration->closed_at);
                    })
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove Selected')
                ]),
            ]);
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

            $celebration = $this->getOwnerRecord();

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
                ->body("A free $playgroundProduct->name ticket has been created for {$child->first_name}")
                ->success()
                ->actions([
                    Action::make('print')
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
                'celebration_id' => $this->getOwnerRecord()->id ?? null,
            ]);

            Notification::make()
                ->title('Ticket creation failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
