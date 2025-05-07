<?php

namespace App\Filament\Clusters\Cashier\Concerns;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Session;

trait HasGlobalUserSearch
{
    public ?string $selectedUserId = null;
    public ?User $selectedUser = null;
    
    // Livewire lifecycle hook that will be called automatically
    public function bootHasGlobalUserSearch(): void
    {
        $this->selectedUserId = session('cashier.selected_user_id');
        
        if ($this->selectedUserId) {
            // Load user with family but without children
            $this->selectedUser = User::with([
                'profile', 
                'family'
            ])->find($this->selectedUserId);
            
            \Illuminate\Support\Facades\Log::info('Boot - User selection restored from session', [
                'selectedUserId' => $this->selectedUserId,
                'has_user' => (bool)$this->selectedUser,
                'has_family' => $this->selectedUser && $this->selectedUser->family ? true : false,
                'family_loaded' => $this->selectedUser ? $this->selectedUser->relationLoaded('family') : false,
                'family_id' => $this->selectedUser && $this->selectedUser->family ? $this->selectedUser->family->id : null,
            ]);
        }
    }
    
    public function selectUser(?string $userId): void
    {
        \Illuminate\Support\Facades\Log::info('Select User called', ['userId' => $userId]);
        
        if ($userId) {
            Session::put('cashier.selected_user_id', $userId);
            $this->selectedUserId = $userId;
            
            // Load user with family but without children to avoid deep nesting issues
            $this->selectedUser = User::with([
                'profile', 
                'family'
            ])->find($userId);
            
            // Add debug logging to check relationships
            if ($this->selectedUser) {
                \Illuminate\Support\Facades\Log::info('User selected', [
                    'user_id' => $userId,
                    'selectedUserId' => $this->selectedUserId,
                    'has_family' => $this->selectedUser->family ? true : false,
                    'family_loaded' => $this->selectedUser->relationLoaded('family'),
                    'family_id' => $this->selectedUser->family ? $this->selectedUser->family->id : null,
                ]);
            }
        } else {
            Session::forget('cashier.selected_user_id');
            $this->selectedUserId = null;
            $this->selectedUser = null;
        }
        
        // Dispatch an event to notify components that user has been selected
        $this->dispatch('user-selected', userId: $userId);
        
        // This ensures the whole component is re-initialized
        if (method_exists($this, 'reset')) {
            $this->reset(['data']);
        }
    }
    
    public function getUserSearchField(): Select
    {
        return Select::make('selectedUserId')
            ->label('Search Client')
            ->placeholder('Search by name, email or phone...')
            ->searchable()
            ->native(false)
            ->allowHtml()
            ->preload()
            ->searchDebounce(300)
            ->getSearchResultsUsing(function (string $search): array {
                if (empty($search) || strlen($search) < 2) {
                    return [];
                }

                return User::query()
                    ->where(function ($query) use ($search) {
                        $query->where('email', 'ILIKE', "%{$search}%")
                            ->orWhereHas('profile', function ($q) use ($search) {
                                $q->where('first_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('phone_number', 'ILIKE', "%{$search}%");
                            });
                    })
                    ->with(['profile', 'family'])
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn($user) => [
                        $user->id => view('filament.clusters.cashier.components.user-option', ['user' => $user])->render(),
                    ])
                    ->toArray();
            })
            ->getOptionLabelUsing(function ($value) {
                $user = User::with(['profile', 'family'])->find($value);
                return $user ? view('filament.clusters.cashier.components.user-option', ['user' => $user])->render() : '';
            })
            ->live()
            ->afterStateUpdated(function ($state) {
                $this->selectUser($state);
            });
    }
    
    public function clearSelectedUser(): void
    {
        $this->selectUser(null);
    }
} 