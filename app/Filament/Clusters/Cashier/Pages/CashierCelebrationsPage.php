<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Exceptions\InsufficientBalanceException;
use App\Filament\Clusters\Cashier;
use App\Models\Celebration;
use App\Models\User;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class CashierCelebrationsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';
    protected static string $view = 'filament.clusters.cashier.pages.cashier-celebrations';

    protected static ?string $navigationLabel = 'Celebration Payments';
    protected static ?string $title = 'Celebration Payments';

    protected $queryString = [
        'transaction' => ['except' => null],
    ];

    public ?array $data = [
        'user_id' => null,
        'celebration_id' => null,
        'amount' => null,
    ];

    public ?Collection $celebrations = null;
    public ?User $client = null;

    #[Url]
    public ?string $transaction = null;
    public array $receipt = [];
    public string $step = 'payment';

    public function mount(): void
    {
        $this->receipt = $this->transaction ? Cache::get("cashier.celebration.{$this->transaction}", []) : [];
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Client')
                    ->searchable()
                    ->native(false)
                    ->allowHtml()
                    ->required()
                    ->live()
                    ->getSearchResultsUsing(function (string $search): array {
                        return User::query()
                            ->where('email', 'ILIKE', "%{$search}%")
                            ->orWhereHas('profile', function ($q) use ($search) {
                                $q->where('first_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('phone_number', 'ILIKE', "%{$search}%");
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
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            $this->client = User::with('family')->find($state);
                            $this->celebrations = Celebration::where('family_id', $this->client->family_id)
                                ->where('completed', false)
                                ->with(['child', 'package', 'theme'])
                                ->get();
                        } else {
                            $this->client = null;
                            $this->celebrations = null;
                        }
                    }),
                Select::make('celebration_id')
                    ->label('Celebration')
                    ->native(false)
                    ->options(function () {
                        if (!$this->celebrations) {
                            return [];
                        }

                        return $this->celebrations->mapWithKeys(function ($celebration) {
                            $remaining = $celebration->total_amount - $celebration->paid_amount;
                            $formattedRemaining = app(FormatterServiceInterface::class)->floatValue($remaining, 2);

                            return [
                                $celebration->id => "{$celebration->child->first_name}'s Celebration - {$celebration->package->name} - {$celebration->celebration_date->format('d.m.Y')} - Balance Due: {$formattedRemaining}"
                            ];
                        })->toArray();
                    })
                    ->required()
                    ->live()
                    ->disabled(fn(Get $get): bool => blank($get('user_id')))
                    ->afterStateUpdated(function ($state, Get $get) {
                        if ($state && $this->celebrations) {
                            $celebration = $this->celebrations->firstWhere('id', $state);
                            if ($celebration) {
                                $remaining = $celebration->total_amount - $celebration->paid_amount;
                                $this->data['amount'] = $remaining;
                            }
                        }
                    }),
                TextInput::make('amount')
                    ->label('Payment Amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->maxValue(function (Get $get) {
                        if (!$get('celebration_id') || !$this->celebrations) {
                            return null;
                        }

                        $celebration = $this->celebrations->firstWhere('id', $get('celebration_id'));
                        return $celebration ? $celebration->total_amount - $celebration->paid_amount : null;
                    })
                    ->disabled(fn(Get $get): bool => blank($get('celebration_id')))
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            $client = User::with('family')->findOrFail($data['user_id']);
            $celebration = Celebration::findOrFail($data['celebration_id']);
            $amount = (float) $data['amount'];

            throw_unless($client->family->canWithdraw($amount),
                new InsufficientBalanceException(
                    amount: $amount,
                    balance: $client->family->main_wallet->balance,
                )
            );

            // Process the payment
            $client->family->withdraw($amount, [
                'description' => "Payment for celebration #{$celebration->id}",
                'celebration_id' => $celebration->id,
            ]);

            // Update the celebration paid amount
            $celebration->paid_amount = $celebration->paid_amount + $amount;
            $celebration->save();

            $cashierUser = Filament::auth()->user();
            $transactionId = (string)Str::uuid7(time: now());

            $receipt = [
                'transaction_id' => $transactionId,
                'celebration_id' => $celebration->id,
                'client_name' => $client->profile->full_name,
                'child_name' => $celebration->child->first_name,
                'amount' => $amount,
                'date' => Carbon::now()->format('d.m.Y H:i'),
                'cashier' => $cashierUser->name,
                'remaining' => $celebration->total_amount - $celebration->paid_amount,
            ];

            DB::commit();
            Cache::put("cashier.celebration.{$transactionId}", $receipt, now()->addDays(2));

            $this->receipt = $receipt;
            $this->transaction = $transactionId;
            $this->step = 'receipt';

            Notification::make()
                ->title('Payment processed successfully.')
                ->success()
                ->send();

        } catch (ExceptionInterface $e) {
            DB::rollBack();
            Notification::make()
                ->title('Failed to process payment.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function back()
    {
        $this->transaction = null;
        $this->step = 'payment';
    }
}
