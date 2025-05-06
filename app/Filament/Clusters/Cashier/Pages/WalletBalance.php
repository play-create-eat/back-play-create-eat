<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Models\Family;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Wallet;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WalletBalance extends Page
{
    use InteractsWithForms;

    protected static ?string $cluster = Cashier::class;

    protected static string $view = 'filament.clusters.cashier.pages.wallet-balance';

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'Top Up Wallet';
    protected static ?string $title = 'Top Up Wallet';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('family_id')
                    ->label('Select Family')
                    ->options(Family::pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('wallet_type')
                    ->label('Wallet Type')
                    ->options([
                        'main'     => 'Main Wallet',
                        'cashback' => 'Cashback Wallet',
                    ])
                    ->required(),

                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->minValue(2)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $family = Family::findOrFail($data['family_id']);

        $wallet = match ($data['wallet_type']) {
            'cashback' => $family->loyalty_wallet,
            default => $family->main_wallet,
        };

        try {
            $wallet->deposit((float) $data['amount']);
        } catch (ExceptionInterface $e) {

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

        $this->form->fill();
    }
}
