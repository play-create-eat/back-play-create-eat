<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Models\Family;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class WalletBalance extends Page
{
    use InteractsWithForms;
    use HasGlobalUserSearch;

    protected static ?string $cluster = Cashier::class;

    protected static string $view = 'filament.clusters.cashier.pages.wallet-balance';

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'Top Up Wallet';
    protected static ?string $title = 'Top Up Wallet';

    public array $data = [];
    public array $recentTransactions = [];

    public function mount(): void
    {
        // Initialize user selection
        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }
        
        $this->loadTransactions();
        $this->form->fill();
    }

    // Add event listener for user selection to refresh the form
    protected function getListeners(): array
    {
        return [
            'user-selected' => 'refreshForm',
        ];
    }
    
    // Method to refresh the form after user selection
    public function refreshForm(): void
    {
        Log::info('Refreshing wallet form', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser' => (bool)$this->selectedUser,
        ]);
        
        // Ensure the user and their family are properly loaded
        if ($this->selectedUserId) {
            $this->selectedUser = \App\Models\User::with([
                'profile', 
                'family',
                'family.children'
            ])->find($this->selectedUserId);
            
            $this->loadTransactions();
        }
        
        // Reset form data
        $this->data = [];
        
        // Force re-render the form with the current state
        $this->form->fill();
    }
    
    protected function loadTransactions(): void
    {
        $this->recentTransactions = [];
        
        if (!$this->selectedUser || !$this->selectedUser->family) {
            return;
        }
        
        $family = $this->selectedUser->family;
        
        // Get main wallet transactions
        $mainWalletTransactions = Transaction::where('payable_type', Family::class)
            ->where('payable_id', $family->id)
            ->where('wallet_id', $family->main_wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function (Transaction $transaction) {
                $description = 'Unknown transaction';
                
                if (!empty($transaction->meta) && isset($transaction->meta['description'])) {
                    $description = $transaction->meta['description'];
                } elseif ($transaction->type === 'deposit') {
                    $description = 'Wallet top-up';
                } elseif ($transaction->type === 'withdraw') {
                    $description = 'Purchase';
                }
                
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => app(FormatterServiceInterface::class)->floatValue($transaction->amount, 2),
                    'date' => $transaction->created_at->format('d.m.Y H:i'),
                    'wallet' => 'Main Wallet',
                    'description' => $description,
                    'meta' => $transaction->meta ? (string) json_encode($transaction->meta) : null,
                ];
            });
            
        // Get loyalty wallet transactions
        $loyaltyWalletTransactions = Transaction::where('payable_type', Family::class)
            ->where('payable_id', $family->id)
            ->where('wallet_id', $family->loyalty_wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function (Transaction $transaction) {
                $description = 'Unknown transaction';
                
                if (!empty($transaction->meta) && isset($transaction->meta['description'])) {
                    $description = $transaction->meta['description'];
                } elseif ($transaction->type === 'deposit') {
                    $description = 'Cashback reward';
                } elseif ($transaction->type === 'withdraw') {
                    $description = 'Loyalty redemption';
                }
                
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => app(FormatterServiceInterface::class)->floatValue($transaction->amount, 2),
                    'date' => $transaction->created_at->format('d.m.Y H:i'),
                    'wallet' => 'Cashback Wallet',
                    'description' => $description,
                    'meta' => $transaction->meta ? (string) json_encode($transaction->meta) : null,
                ];
            });
            
        // Combine and sort transactions
        $this->recentTransactions = $mainWalletTransactions->concat($loyaltyWalletTransactions)
            ->sortByDesc('date')
            ->take(15)
            ->values()
            ->toArray();
            
        Log::info('Loaded transactions', [
            'count' => count($this->recentTransactions),
        ]);
    }

    public function form(Form $form): Form
    {
        Log::info('Wallet form method called', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser' => (bool)$this->selectedUser,
        ]);
        
        return $form
            ->schema([
                $this->getUserSearchField()
                    ->hiddenOn('edit')
                    ->visible(fn() => !$this->selectedUser)
                    ->columnSpanFull(),
                
                Select::make('wallet_type')
                    ->label('Wallet Type')
                    ->options([
                        'main'     => 'Main Wallet',
                        'cashback' => 'Cashback Wallet',
                    ])
                    ->default('main')
                    ->required()
                    ->visible(fn() => (bool)$this->selectedUser),

                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->minValue(2)
                    ->suffix('€')
                    ->required()
                    ->visible(fn() => (bool)$this->selectedUser),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        if (!$this->selectedUser || !$this->selectedUser->family) {
            Notification::make()
                ->title('No user selected')
                ->body('Please select a user first.')
                ->danger()
                ->send();
            return;
        }
        
        $data = $this->form->getState();
        $family = $this->selectedUser->family;

        $wallet = match ($data['wallet_type']) {
            'cashback' => $family->loyalty_wallet,
            default => $family->main_wallet,
        };

        try {
            $wallet->deposit((float) $data['amount'], [
                'description' => 'Manual top-up by cashier',
                'cashier_id' => auth()->id(),
            ]);
            
            Log::info('Wallet topped up', [
                'user_id' => $this->selectedUserId,
                'family_id' => $family->id,
                'wallet_type' => $data['wallet_type'],
                'amount' => $data['amount'],
            ]);
        } catch (ExceptionInterface $e) {
            Log::error('Failed to top up wallet', [
                'user_id' => $this->selectedUserId,
                'family_id' => $family->id,
                'wallet_type' => $data['wallet_type'],
                'amount' => $data['amount'],
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Failed to top up wallet.')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }

        Notification::make()
            ->title('Wallet topped up successfully.')
            ->body("{$data['amount']} was added to the {$data['wallet_type']} wallet.")
            ->success()
            ->send();

        // Reload transactions to reflect new deposit
        $this->loadTransactions();
        
        // Reset the form and keep the same user selected
        $this->data = [];
        $this->form->fill();
    }
}
