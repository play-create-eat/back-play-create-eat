<?php

namespace App\Filament\Clusters\Cashier\Resources\CelebrationResource\RelationManagers;

use App\Models\Child;
use App\Models\Product;
use App\Services\PassService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CelebrationChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'invitations';

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
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male'   => 'Male',
                        'female' => 'Female',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->label('Add Child')
                    ->modalHeading('Add Child to Celebration')
                    ->modalDescription('Select a child to add to this celebration')
                    ->recordTitle(fn(Child $record) => "$record->first_name $record->last_name")
                    ->recordSelect(fn(Select $select) => $select
                        ->getOptionLabelFromRecordUsing(fn(Child $record) => "$record->first_name $record->last_name")
                        ->searchable(['first_name', 'last_name']))
                    ->after(function (array $data) {
                        $childId = $data['recordId'] ?? null;

                        if ($childId) {
                            $child = Child::find($childId);
                            if ($child) {
                                $this->createFreePlaygroundTicket($child);
                            }
                        }
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('printTicket')
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
                Tables\Actions\Action::make('recreateTicket')
                    ->label('Recreate Ticket')
                    ->icon('heroicon-o-ticket')
                    ->color('primary')
                    ->button()
                    ->action(function (Child $record) {
                        $this->createFreePlaygroundTicket($record);
                    }),
                Tables\Actions\DetachAction::make()
                    ->label('Remove')
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
                ->body("A free {$playgroundProduct->name} ticket has been created for {$child->first_name}")
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
