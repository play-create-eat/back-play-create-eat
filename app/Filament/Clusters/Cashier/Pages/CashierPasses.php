<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Models\Child;
use App\Models\Pass;
use App\Models\User;
use App\Services\PassService;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;

class CashierPasses extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasGlobalUserSearch;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static string $view = 'filament.clusters.cashier.pages.passes';

    protected static ?string $navigationLabel = 'Passes';
    protected static ?string $title = 'Client Passes';
    
    public function mount(): void
    {
        // Initialize user selection
        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }
        
        // Initialize table properly to prevent array key errors
        $this->tableFilters = [];
        
        // Don't apply default filters during mount - Filament will handle this later
        // We can't access $this->table yet as it's not initialized
    }

    // Remove the applyDefaultTableFilters method since we shouldn't call it during mount
    
    // Use Filament's built-in life cycle hooks instead
    protected function onTableInitialized(): void
    {
        // This method is called by Filament after the table is initialized
        // Now it's safe to apply default filters
        if ($this->selectedUser && empty($this->tableFilters)) {
            // Don't use resetTableSearch here as it might trigger unwanted behavior
            $this->tableFilters['ticket_status'] = ['value' => ['active']];
        }
    }

    // Add event listener for user selection to refresh the table
    protected function getListeners(): array
    {
        return [
            'user-selected' => 'refreshTable',
        ];
    }
    
    // Method to refresh the table after user selection
    public function refreshTable(): void
    {
        // Only call resetTable if we're sure the table is initialized
        if (method_exists($this, 'resetTable') && property_exists($this, 'table') && isset($this->table)) {
            $this->resetTable();
        } else {
            // If table is not initialized yet, just initialize the filters
            $this->tableFilters = [];
        }
    }
    
    /**
     * Filter by specific child
     * 
     * @param string|null $childId
     * @return void
     */
    public function filterByChild(?string $childId = null): void
    {
        try {
            if ($childId) {
                // Set the children filter
                $this->setTableFilters([
                    'children' => [$childId],
                ]);
            } else {
                // Clear all filters
                if (method_exists($this, 'resetTableFilters') && property_exists($this, 'tableFilters')) {
                    $this->resetTableFilters();
                } else {
                    $this->tableFilters = [];
                    $this->dispatch('table-filters-reset');
                }
            }
            
            // Force a refresh
            $this->dispatch('table-filtered');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error filtering by child: " . $e->getMessage());
        }
    }
    
    /**
     * Set the ticket status filter
     * 
     * @param array $statuses
     * @return void
     */
    public function filterByTicketStatus(array $statuses): void
    {
        try {
            // Use Livewire's filter functions directly
            $this->setTableFilters([
                'ticket_status' => $statuses,
            ]);
            
            // Force a refresh
            $this->dispatch('table-filtered');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error filtering by ticket status: " . $e->getMessage());
        }
    }
    
    /**
     * Set table filters from the UI
     * 
     * @param array $filters
     * @return void
     */
    public function setTableFilters($filters = []): void
    {
        // Ensure tableFilters is initialized as an array
        if (!isset($this->tableFilters) || !is_array($this->tableFilters)) {
            $this->tableFilters = [];
        }
        
        // Ensure filters is an array
        if (!is_array($filters)) {
            return;
        }
        
        // Clear any existing filters if the filters array is empty
        if (empty($filters)) {
            if (method_exists($this, 'resetTableFilters') && property_exists($this, 'table') && isset($this->table)) {
                $this->resetTableFilters();
            } else {
                $this->tableFilters = [];
            }
            
            if (method_exists($this, 'resetTableSearch') && property_exists($this, 'table') && isset($this->table)) {
                $this->resetTableSearch();
            }
            
            if (method_exists($this, 'resetTableColumnSearches') && property_exists($this, 'table') && isset($this->table)) {
                $this->resetTableColumnSearches();
            }
            
            return;
        }
        
        foreach ($filters as $filterName => $filterValue) {
            // Ensure filter name is a valid array key (string or integer)
            if (!is_string($filterName) && !is_int($filterName)) {
                continue;
            }
            
            // Cast the filter name to string to ensure it's a valid array key
            $filterName = (string)$filterName;
            
            try {
                // Handle array values (most common case)
                if (is_array($filterValue)) {
                    $this->tableFilters[$filterName] = ['value' => $filterValue];
                } 
                // Handle scalar values
                else if (is_scalar($filterValue)) {
                    $this->tableFilters[$filterName] = ['value' => [$filterValue]];
                }
            } catch (\Throwable $e) {
                // Log error but continue processing other filters
                \Illuminate\Support\Facades\Log::error("Error setting table filter {$filterName}: " . $e->getMessage());
            }
        }
        
        // Only call resetTableSearch if we're sure the table is initialized
        if (method_exists($this, 'resetTableSearch') && property_exists($this, 'table') && isset($this->table)) {
            $this->resetTableSearch();
        }
        
        // Only call resetTableColumnSearches if we're sure the table is initialized
        if (method_exists($this, 'resetTableColumnSearches') && property_exists($this, 'table') && isset($this->table)) {
            $this->resetTableColumnSearches();
        }
    }

    public function table(Table $table): Table
    {
        $query = Pass::query();
        
        // Apply user filter if a user is selected
        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser->id);
        } else {
            // Return empty query if no user is selected
            $query->whereRaw('1 = 0'); // Always false condition
        }
        
        // Eager load relations to avoid N+1 issues
        $query->with(['user.profile', 'user.family', 'children']);
            
        return $table
            ->query($query)
            ->columns([
                TextColumn::make('serial')
                    ->searchable()
                    ->getStateUsing(fn(Pass $record): HtmlString => new HtmlString("<span class='text-xs font-mono'>{$record->serial}</span>")),
                TextColumn::make('children')
                    ->label('Child')
                    ->getStateUsing(function (Pass $record): string {
                        return $record->children?->full_name ?? 'No child assigned';
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('remaining_time')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn(string $state) => CarbonInterval::minutes($state)->cascade()->forHumans([
                        'join' => true,
                        'parts' => 3,
                    ])),
                IconColumn::make('is_extendable')
                    ->boolean(),
                TextColumn::make('entered_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('exited_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('activation_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->getStateUsing(function (Pass $record) {
                        if ($record->isExpired()) {
                            return 'Expired';
                        }
                        
                        if ($record->entered_at && !$record->exited_at) {
                            return 'Checked in';
                        }
                        
                        if ($record->activation_date->isToday()) {
                            return 'Active today';
                        }
                        
                        if ($record->activation_date->isFuture()) {
                            return 'Future';
                        }
                        
                        return 'Inactive';
                    })
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'Expired' => 'danger',
                            'Checked in' => 'success',
                            'Active today' => 'success',
                            'Future' => 'warning',
                            default => 'gray',
                        };
                    })
            ])
            ->filters([
                SelectFilter::make('children')
                    ->label('Filter by child')
                    ->options(function () {
                        if (!$this->selectedUser || !$this->selectedUser->family) {
                            return [];
                        }
                        
                        return $this->selectedUser->family->children
                            ->mapWithKeys(fn(Child $child) => [
                                $child->id => "{$child->first_name} {$child->last_name}"
                            ])
                            ->toArray();
                    })
                    ->attribute('child_id')
                    ->preload()
                    ->multiple()
                    ->indicator('Child'),
                SelectFilter::make('ticket_status')
                    ->label('Ticket Status')
                    ->options([
                        'active' => 'Active today',
                        'future' => 'Future tickets',
                        'expired' => 'Expired tickets',
                    ])
                    ->default(['active'])
                    ->query(function (Builder $query, array $data) {
                        // Safely check if value exists and is an array
                        if (!isset($data['value']) || !is_array($data['value'])) {
                            return $query;
                        }
                        
                        // If filter array is empty, return the query unmodified
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $today = Carbon::today();
                        
                        return $query->where(function (Builder $query) use ($data, $today) {
                            // Safely check for each status
                            if (is_array($data['value']) && in_array('active', $data['value'], true)) {
                                $query->orWhere(function ($q) use ($today) {
                                    $q->whereDate('activation_date', $today)
                                      ->where('expires_at', '>=', now());
                                });
                            }
                            
                            if (is_array($data['value']) && in_array('future', $data['value'], true)) {
                                $query->orWhere(function ($q) use ($today) {
                                    $q->whereDate('activation_date', '>', $today);
                                });
                            }
                            
                            if (is_array($data['value']) && in_array('expired', $data['value'], true)) {
                                $query->orWhere('expires_at', '<', now());
                            }
                        });
                    })
                    ->indicator('Status'),
                SelectFilter::make('check_status')
                    ->label('Check Status')
                    ->options([
                        'checked_in' => 'Checked in',
                        'not_checked' => 'Not checked in',
                    ])
                    ->query(function (Builder $query, array $data) {
                        // Safely check if value exists and is an array
                        if (!isset($data['value']) || !is_array($data['value'])) {
                            return $query;
                        }
                        
                        // If filter array is empty, return the query unmodified
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        return $query->where(function (Builder $query) use ($data) {
                            // Safely check for each status
                            if (is_array($data['value']) && in_array('checked_in', $data['value'], true)) {
                                $query->orWhere(function ($q) {
                                    $q->whereNotNull('entered_at')
                                      ->whereNull('exited_at');
                                });
                            }
                            
                            if (is_array($data['value']) && in_array('not_checked', $data['value'], true)) {
                                $query->orWhere(function ($q) {
                                    $q->whereNull('entered_at')
                                      ->orWhereNotNull('exited_at');
                                });
                            }
                        });
                    })
                    ->indicator('Check Status'),
            ])
            ->filtersFormColumns(3)
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersTriggerAction(
                fn(\Filament\Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filters'),
            )
            ->actions([
                Action::make('print')
                    ->label('Print')
                    ->url(fn(Pass $record) => route('filament.admin.pass.print', ['serial' => $record->serial]))
                    ->openUrlInNewTab()
                    ->button(),
                Action::make('extend')
                    ->label('Extend')
                    ->visible(fn(Pass $record) => $record->is_extendable && !$record->isExpired())
                    ->form([
                        \Filament\Forms\Components\TextInput::make('minutes')
                            ->label('Minutes to extend')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (Pass $record, array $data) {
                        try {
                            app(PassService::class)->extend($record->serial, $data['minutes']);
                            
                            Notification::make()
                                ->title('Pass extended successfully')
                                ->success()
                                ->send();
                                
                            $this->refreshTable();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to extend pass')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ], position: ActionsPosition::BeforeColumns)
            ->emptyStateHeading('No passes found')
            ->emptyStateDescription(function () {
                if (!$this->selectedUser) {
                    return 'Please select a client to view their passes.';
                }
                
                return 'No passes found for this client.';
            })
            ->emptyStateIcon('heroicon-o-ticket')
            ->persistFiltersInSession();
    }

    /**
     * Safely render the table field
     * 
     * @return string
     */
    public function renderTableField()
    {
        // In Filament v3, we need to use a different approach for table rendering
        if (method_exists($this, 'getTableContent')) {
            return $this->getTableContent();
        }
        
        try {
            // Try to access the table property safely
            if (property_exists($this, 'table') && isset($this->table)) {
                // Return an HTML message for debugging
                return '<div class="p-4 bg-green-50 text-green-700 rounded-md">
                            <p>Table exists but cannot be rendered directly. Please check for updated Filament functionality.</p>
                        </div>';
            }
            
            return '<div class="p-4 bg-yellow-50 text-yellow-700 rounded-md">
                        <p>Table is not initialized yet. Please try refreshing the page.</p>
                    </div>';
        } catch (\Throwable $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error("Error rendering table: " . $e->getMessage());
            
            // Return a fallback message
            return '<div class="p-4 bg-red-50 text-red-700 rounded-md">
                        <p>Error rendering table: ' . htmlspecialchars($e->getMessage()) . '</p>
                        <p class="text-sm mt-2">Please try refreshing the page. If the issue persists, contact support.</p>
                    </div>';
        }
    }

    /**
     * Get the table for the view
     * 
     * @return \Filament\Tables\Table
     */
    public function getTable(): \Filament\Tables\Table
    {
        try {
            // Build table with our configuration
            return $this->table(new \Filament\Tables\Table($this));
        } catch (\Throwable $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error("Error creating table: " . $e->getMessage());
            
            // Return a minimal table as fallback
            return new \Filament\Tables\Table($this);
        }
    }

    /**
     * Render a custom table when Filament's table fails
     * 
     * @return string
     */
    public function renderCustomTable(): string
    {
        try {
            // Create a simple list view as a fallback
            if ($this->selectedUser) {
                $passes = \App\Models\Pass::where('user_id', $this->selectedUser->id)
                    ->with(['user.profile', 'user.family', 'children'])
                    ->get();
                
                if ($passes->isEmpty()) {
                    return "<div class='p-4 bg-blue-50 text-blue-700 rounded-md'>No passes found for this user.</div>";
                }
                
                $html = "<div class='overflow-x-auto'><table class='min-w-full divide-y divide-gray-200'>";
                
                // Table header
                $html .= "<thead class='bg-gray-50'><tr>";
                $html .= "<th class='px-4 py-2 text-left'>Serial</th>";
                $html .= "<th class='px-4 py-2 text-left'>Child</th>";
                $html .= "<th class='px-4 py-2 text-left'>Remaining Time</th>";
                $html .= "<th class='px-4 py-2 text-left'>Status</th>";
                $html .= "<th class='px-4 py-2 text-left'>Actions</th>";
                $html .= "</tr></thead>";
                
                // Table body
                $html .= "<tbody>";
                foreach ($passes as $pass) {
                    $html .= "<tr class='border-b'>";
                    $html .= "<td class='px-4 py-2 font-mono text-xs'>{$pass->serial}</td>";
                    $html .= "<td class='px-4 py-2'>" . ($pass->children?->full_name ?? 'No child assigned') . "</td>";
                    $html .= "<td class='px-4 py-2'>" . \Carbon\CarbonInterval::minutes($pass->remaining_time)->cascade()->forHumans(['join' => true, 'parts' => 3]) . "</td>";
                    
                    // Status
                    $status = 'Inactive';
                    $color = 'gray';
                    
                    if ($pass->isExpired()) {
                        $status = 'Expired';
                        $color = 'red';
                    } elseif ($pass->entered_at && !$pass->exited_at) {
                        $status = 'Checked in';
                        $color = 'green';
                    } elseif ($pass->activation_date->isToday()) {
                        $status = 'Active today';
                        $color = 'green';
                    } elseif ($pass->activation_date->isFuture()) {
                        $status = 'Future';
                        $color = 'yellow';
                    }
                    
                    $html .= "<td class='px-4 py-2'><span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{$color}-100 text-{$color}-800'>{$status}</span></td>";
                    
                    // Actions
                    $html .= "<td class='px-4 py-2'>";
                    $html .= "<div class='flex space-x-2'>";
                    $html .= "<a href='" . route('filament.admin.pass.print', ['serial' => $pass->serial]) . "' target='_blank' class='px-3 py-1 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700'>Print</a>";
                    
                    if ($pass->is_extendable && !$pass->isExpired()) {
                        $html .= "<button wire:click=\"mountTableAction('extend', '{$pass->serial}')\" class='px-3 py-1 bg-green-600 text-white text-xs rounded-md hover:bg-green-700'>Extend</button>";
                    }
                    
                    $html .= "</div>";
                    $html .= "</td>";
                    $html .= "</tr>";
                }
                $html .= "</tbody>";
                $html .= "</table></div>";
                
                return $html;
            }
            
            return "<div class='p-4 bg-yellow-50 text-yellow-700 rounded-md'>No user selected or table could not be rendered.</div>";
        } catch (\Throwable $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error("Error rendering table: " . $e->getMessage());
            
            // Return a fallback message
            return '<div class="p-4 bg-red-50 text-red-700 rounded-md">
                        <p>Error rendering table: ' . htmlspecialchars($e->getMessage()) . '</p>
                        <p class="text-sm mt-2">Please try refreshing the page. If the issue persists, contact support.</p>
                    </div>';
        }
    }

    /**
     * Check if the table should be shown
     * 
     * @return bool
     */
    public function shouldShowTable(): bool
    {
        return $this->selectedUser !== null;
    }

    /**
     * Mount a table action
     * 
     * @param string $name The action name
     * @param string|null $record The record ID or serial
     * @param array $arguments Additional arguments
     * @return mixed
     */
    public function mountTableAction(string $name, string|null $record = null, array $arguments = []): mixed
    {
        if ($name === 'extend' && $record) {
            // Find the pass by serial
            $pass = \App\Models\Pass::where('serial', $record)->first();
            
            if (!$pass) {
                // Record not found
                \Filament\Notifications\Notification::make()
                    ->title('Pass not found')
                    ->danger()
                    ->send();
                return null;
            }
            
            // Check if pass is extendable
            if (!$pass->is_extendable || $pass->isExpired()) {
                \Filament\Notifications\Notification::make()
                    ->title('Pass cannot be extended')
                    ->danger()
                    ->send();
                return null;
            }
            
            // Show a form to extend the pass
            return $this->extendPass($pass, $arguments['minutes'] ?? 0);
        }
        
        return null;
    }
    
    /**
     * Extend a pass
     * 
     * @param \App\Models\Pass $pass The pass to extend
     * @param int $minutes Minutes to extend
     * @return mixed
     */
    public function extendPass(\App\Models\Pass $pass, int $minutes = 0): mixed
    {
        // If minutes is 0, show a prompt to enter minutes
        if ($minutes <= 0) {
            // You'd normally show a modal here, but we'll simulate with a JavaScript prompt
            $this->dispatchBrowserEvent('extend-pass', [
                'id' => $pass->id,
                'serial' => $pass->serial,
            ]);
            
            return null;
        }
        
        try {
            // Extend the pass
            app(\App\Services\PassService::class)->extend($pass->serial, $minutes);
            
            \Filament\Notifications\Notification::make()
                ->title('Pass extended successfully')
                ->success()
                ->send();
                
            // Refresh the table
            $this->refreshTable();
            
            return true;
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Failed to extend pass')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            return false;
        }
    }

    /**
     * Get the Filament table to be displayed in the view
     */
    public function getTableProperty()
    {
        // We need to properly initialize the Filament table
        // This will avoid array key exists errors 
        return $this->table(new \Filament\Tables\Table($this));
    }
} 