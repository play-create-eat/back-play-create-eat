<?php

namespace App\Filament\Clusters\Cashier\Concerns;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

trait HasGlobalUserSearch
{
    public ?string $selectedUserId = null;
    public ?User $selectedUser = null;

    public function bootHasGlobalUserSearch(): void
    {
        $this->selectedUserId = session('cashier.selected_user_id');

        if ($this->selectedUserId) {
            $this->selectedUser = User::with([
                'profile',
                'family'
            ])->find($this->selectedUserId);

            Log::info('Boot - User selection restored from session', [
                'selectedUserId' => $this->selectedUserId,
                'has_user' => (bool)$this->selectedUser,
                'has_family' => $this->selectedUser && $this->selectedUser->family,
                'family_loaded' => $this->selectedUser && $this->selectedUser->relationLoaded('family'),
                'family_id' => $this->selectedUser && $this->selectedUser->family ? $this->selectedUser->family->id : null,
            ]);
        }
    }

    public function selectUser(?string $userId): void
    {
        Log::info('Select User called', ['userId' => $userId]);

        if ($userId) {
            Session::put('cashier.selected_user_id', $userId);
            $this->selectedUserId = $userId;

            $this->selectedUser = User::with([
                'profile',
                'family'
            ])->find($userId);

            if ($this->selectedUser) {
                Log::info('User selected', [
                    'user_id' => $userId,
                    'selectedUserId' => $this->selectedUserId,
                    'has_family' => (bool)$this->selectedUser->family,
                    'family_loaded' => $this->selectedUser->relationLoaded('family'),
                    'family_id' => $this->selectedUser->family ? $this->selectedUser->family->id : null,
                ]);
            }
        } else {
            Session::forget('cashier.selected_user_id');
            $this->selectedUserId = null;
            $this->selectedUser = null;
        }

        $this->dispatch('user-selected', userId: $userId);

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
