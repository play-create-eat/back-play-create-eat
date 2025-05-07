<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WalletTopUp extends Page implements HasForms
{
    use InteractsWithForms;
    use HasGlobalUserSearch;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    protected static string $view = 'filament.clusters.cashier.pages.wallet-top-up';

    protected static ?string $navigationLabel = 'Wallet Top-up';
    protected static ?string $title = 'Wallet Top-up';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getUserSearchField()
                    ->hiddenOn('edit')
                    ->visible(fn() => empty($this->selectedUserId))
                    ->columnSpanFull(),
                
                TextInput::make('amount')
                    ->label('Amount (€)')
                    ->numeric()
                    ->minValue(5)
                    ->maxValue(1000)
                    ->step(1)
                    ->required()
                    ->disabled(fn() => empty($this->selectedUserId))
                    ->visible(fn() => !empty($this->selectedUserId))
                    ->columnSpanFull()
            ]);
    }

    public function topUp(): void
    {
        if (empty($this->selectedUserId) || !$this->selectedUser) {
            Notification::make()
                ->title('No user selected')
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();
        
        // Here you would implement the actual top-up logic
        // For demonstration purposes, we'll just show a success notification
        
        Notification::make()
            ->title('Wallet topped up successfully')
            ->success()
            ->send();
            
        // Reset the form after successful top-up
        $this->form->fill();
    }
} 