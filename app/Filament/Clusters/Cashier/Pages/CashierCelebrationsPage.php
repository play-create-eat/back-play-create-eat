<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Exceptions\InsufficientBalanceException;
use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Concerns\HasUserSearchForm;
use App\Models\Celebration;
use App\Models\User;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Log;
use Throwable;

class CashierCelebrationsPage extends Page implements HasForms
{
    use InteractsWithForms;
    use HasGlobalUserSearch;
    use HasUserSearchForm;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';
    protected static string $view = 'filament.clusters.cashier.pages.cashier-celebrations';
    protected static ?string $navigationLabel = 'Celebration Payments';
    protected static ?string $title = 'Celebration Payments';

    protected static ?int $navigationSort = 4;
    protected $queryString = [
        'transaction' => ['except' => null],
    ];
    public ?array $data = [
        'celebration_id' => null,
        'amount'         => null,
    ];
    public ?Collection $celebrations = null;
    #[Url]
    public ?string $transaction = null;
    public array $receipt = [];
    public string $step = 'payment';

    public function mount(): void
    {
        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }

        $this->mountHasUserSearchForm();
        $this->receipt = $this->transaction ? Cache::get("cashier.celebration.{$this->transaction}", []) : [];

        if ($this->selectedUserId) {
            $this->updateCelebrationsForUser();
        }

        $this->form->fill();
    }

    public function refreshForm(): void
    {
        Log::info('Refreshing celebration payments form', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser'        => (bool)$this->selectedUser,
        ]);

        if ($this->selectedUserId) {
            $this->selectedUser = User::with([
                'profile',
                'family',
                'family.children'
            ])->find($this->selectedUserId);

            $this->updateCelebrationsForUser();
        }

        $this->data = [
            'celebration_id' => null,
            'amount'         => null,
        ];

        $this->refreshUserSearchForm();
        $this->form->fill();

    }

    protected function updateCelebrationsForUser(): void
    {
        if ($this->selectedUser && $this->selectedUser->family) {
            $this->celebrations = Celebration::where('family_id', $this->selectedUser->family->id)
                ->where('completed', false)
                ->with(['child', 'package', 'theme'])
                ->get();
        } else {
            $this->celebrations = null;
        }
    }

    public function form(Form $form): Form
    {
        Log::info('Celebration payments form method called', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser'        => (bool)$this->selectedUser,
        ]);

        $schema = [];

        if (!$this->selectedUser) {
            $schema[] = $this->getUserSearchField()
                ->columnSpanFull();
        }

        if ($this->selectedUser) {
            $schema[] = Select::make('celebration_id')
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
                            $celebration->id => "{$celebration->child?->first_name}'s Celebration - {$celebration->package?->name} - {$celebration->celebration_date?->format('d.m.Y')} - Balance Due: $formattedRemaining"
                        ];
                    })->toArray();
                })
                ->required()
                ->live()
                ->disabled(fn() => !$this->selectedUser)
                ->afterStateUpdated(function ($state) {
                    if ($state && $this->celebrations) {
                        $celebration = $this->celebrations->firstWhere('id', $state);
                        if ($celebration) {
                            $remaining = $celebration->total_amount - $celebration->paid_amount;
                            $this->data['amount'] = $remaining / 100;
                        }
                    }
                });

            $schema[] = TextInput::make('amount')
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
                ->disabled(fn(Get $get): bool => blank($get('celebration_id')));
        }
        return $form
            ->schema($schema)
            ->statePath('data');
    }

    /**
     * @throws Throwable
     */
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

        try {
            DB::beginTransaction();

            $client = $this->selectedUser;
            $celebration = Celebration::findOrFail($data['celebration_id']);
            $amount = $data['amount'] * 100;

            throw_unless($client->family->canWithdraw($amount),
                new InsufficientBalanceException(
                    amount: $amount,
                    balance: $client->family->main_wallet->balance,
                )
            );

            $client->family->withdraw($amount, [
                'description'    => "Payment for celebration #$celebration->id",
                'celebration_id' => $celebration->id,
            ]);

            $celebration->update([
                'paid_amount' => $celebration->paid_amount + $amount,
                'completed' => true
            ]);

            $cashierUser = Filament::auth()->user();
            $transactionId = (string)Str::uuid7(time: now());

            $receipt = [
                'transaction_id' => $transactionId,
                'celebration_id' => $celebration->id,
                'client_name'    => $client->full_name,
                'child_name'     => $celebration->child->first_name,
                'amount'         => $amount,
                'date'           => Carbon::now()->format('d.m.Y H:i'),
                'cashier'        => $cashierUser->name,
                'remaining'      => $celebration->total_amount - $celebration->paid_amount,
            ];

            DB::commit();
            Cache::put("cashier.celebration.$transactionId", $receipt, now()->addDays(2));

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

    public function back(): void
    {
        $this->transaction = null;
        $this->step = 'payment';
    }

    public function clearSelectedUser(): void
    {
        $this->selectUser(null);
        $this->refreshUserSearchForm();
        $this->form->fill();
    }

    protected function getListeners(): array
    {
        return [
            'user-selected' => 'refreshForm',
        ];
    }

}
