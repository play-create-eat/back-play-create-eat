<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Concerns\HasUserSearchForm;
use App\Models\Family;
use App\Models\User;
use App\Services\TransactionCancellationService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\RawJs;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;


class WalletBalance extends Page implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasGlobalUserSearch;
    use HasUserSearchForm;

    protected static ?string $cluster = Cashier::class;

    protected static string $view = 'filament.clusters.cashier.pages.wallet-balance';

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'Wallet';

    protected static ?int $navigationSort = 1;

    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('cashier-view-wallet');
    }

    public function canTopUpWallet(): bool
    {
        return auth()->guard('admin')->user()->can('cashier-top-up-wallet');
    }

    public function canCancelTransactions(): bool
    {
        return auth()->guard('admin')->user()->can('cancel-wallet-transactions');
    }

    public function mount(): void
    {
        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }

        $this->mountHasUserSearchForm();
        $this->form->fill();
    }

    public function refreshForm(): void
    {
        Log::info('Refreshing wallet form', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser'        => (bool)$this->selectedUser,
        ]);

        if ($this->selectedUserId) {
            $this->selectedUser = User::with([
                'profile',
                'family',
                'family.children'
            ])->find($this->selectedUserId);
        }

        $this->data = [];
        $this->form->fill();
        $this->refreshUserSearchForm();
        $this->resetTable();
    }

    public function form(Form $form): Form
    {
        Log::info('Wallet form method called', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser'        => (bool)$this->selectedUser,
        ]);

        $schema = [];

        if (!$this->selectedUser) {
            $schema[] = $this->getUserSearchComponent();
        }

        if ($this->selectedUser && $this->canTopUpWallet()) {
            $schema[] = Select::make('payment_method')
                ->label('Payment Method')
                ->options([
                    'card' => 'Card Payment',
                    'cash' => 'Cash Payment',
                ])
                ->default('card')
                ->required()
                ->visible(fn() => (bool)$this->selectedUser && $this->canTopUpWallet());

            $schema[] = TextInput::make('amount')
                ->label('Amount (AED)')
                ->prefix('AED ')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->numeric()
                ->required()
                ->visible(fn() => (bool)$this->selectedUser && $this->canTopUpWallet())
                ->minValue(2);
        }

        return $form
            ->schema($schema)
            ->statePath('data');
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        $query = Transaction::query()->with('wallet');

        if ($this->selectedUser && $this->selectedUser->family) {
            $query->where('payable_type', Family::class)
                ->where('payable_id', $this->selectedUser->family->id);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('uuid')->label('ID')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wallet.name')->label('Wallet Type')
                    ->toggleable(),
                TextColumn::make('type')->sortable()->searchable()->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (Transaction $record) {
                        $cancellationService = app(TransactionCancellationService::class);
                        if ($cancellationService->isCancelled($record)) {
                            return 'Cancelled';
                        }
                        return 'Active';
                    })
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Cancelled',
                    ]),
                TextColumn::make('amount')->money('AED')->getStateUsing(function (Transaction $record) {
                    $amount = $record->amount / 100;
                    if (floor($amount) == $amount) {
                        return 'AED ' . number_format($amount);
                    } else {
                        return 'AED ' . number_format($amount, 2);
                    }
                }),
                TextColumn::make('meta.description')->label('Description')->wrap()
                    ->getStateUsing(function (Transaction $record) {
                        $meta = $record->meta ?? [];
                        $description = $meta['description'] ?? '';

                        if (isset($meta['pass_info'])) {
                            $passInfo = $meta['pass_info'];
                            $passId = $passInfo['pass_id'] ?? 'N/A';
                            $childName = $passInfo['child_name'] ?? 'N/A';
                            $activationDate = $passInfo['activation_date'] ?? 'N/A';
                            $expiresAt = $passInfo['expires_at'] ?? 'N/A';

                            if ($expiresAt !== 'N/A') {
                                try {
                                    $expiresAt = Carbon::parse($expiresAt)->format('Y-m-d');
                                } catch (Exception $e) {
                                    $expiresAt = 'N/A';
                                }
                            }


                            $passDetailsHtml = "
                                <div class='text-sm text-gray-600 mt-1'>
                                <div><strong>Pass ID:</strong>$passId</div>
                                <div><strong>Child:</strong> $childName</div>
                                <div><strong>Activation:</strong> $activationDate</div>
                                <div><strong>Expires:</strong>$expiresAt</div>
                                </div>
                            ";

                            return new HtmlString($description . $passDetailsHtml);
                        }

                        return $description;
                    })
                    ->html(),
                TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
            ])
            ->actions([
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                                        ->visible(function (Transaction $record) {
                        $cancellationService = app(TransactionCancellationService::class);
                        return $cancellationService->isCancellable($record) &&
                               $this->canCancelTransactions();
                    })
                    ->form([
                        Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->placeholder('Please provide a reason for cancelling this transaction...'),
                    ])
                    ->action(function (Transaction $record, array $data) {
                        try {
                            $cancellationService = app(TransactionCancellationService::class);
                            $cancellationService->cancelDeposit($record, $data['reason']);

                            Notification::make()
                                ->title('Transaction Cancelled')
                                ->body('The deposit transaction has been successfully cancelled.')
                                ->success()
                                ->send();

                            // Refresh the table and form
                            $this->resetTable();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Cancellation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Transaction')
                    ->modalDescription('Are you sure you want to cancel this deposit transaction? This action will reverse the deposit and cannot be undone.')
                    ->modalSubmitActionLabel('Cancel Transaction'),
            ])
            ->filters([
                SelectFilter::make('wallet_id')
                    ->label('Wallet')
                    ->options(function () {
                        if (!$this->selectedUser || !$this->selectedUser->family) {
                            return [];
                        }

                        $walletIds = Transaction::where('payable_type', Family::class)
                            ->where('payable_id', $this->selectedUser->family->id)
                            ->pluck('wallet_id')
                            ->unique();

                        return Wallet::whereIn('id', $walletIds)
                            ->pluck('name', 'id')
                            ->toArray();

                    }),

                SelectFilter::make('type')
                    ->options([
                        'deposit'  => 'Deposit',
                        'withdraw' => 'Withdraw',
                    ])
                    ->label('Transaction Type'),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'cancelled' => 'Cancelled',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        $cancellationService = app(TransactionCancellationService::class);
                        $cancelledUuids = $cancellationService->getCancelledTransactionUuids();

                        if ($data['value'] === 'cancelled') {
                            return $query->whereIn('uuid', $cancelledUuids)
                                ->orWhere('meta->cancelled', true);
                        } else {
                            return $query->whereNotIn('uuid', $cancelledUuids)
                                ->where(function ($q) {
                                    $q->whereNull('meta->cancelled')
                                      ->orWhere('meta->cancelled', false);
                                });
                        }
                    })
                    ->label('Transaction Status'),

            ])
            ->emptyStateHeading('No transactions found')
            ->emptyStateDescription(fn() => $this->selectedUserId
                ? 'No transactions found for this user.'
                : 'Please select a user to view their transactions.')
            ->emptyStateIcon('heroicon-o-receipt-refund')
            ->defaultSort('created_at', 'desc')
            ->searchable();
    }

    public function submit(): void
    {
        if (!$this->canTopUpWallet()) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to top up wallets.')
                ->danger()
                ->send();
            return;
        }

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

        $paymentMethod = match ($data['payment_method']) {
            'cash' => 'Cash Payment',
            default => 'Card Payment',
        };

        $description = "Manual top-up by cashier ($paymentMethod)";

        try {
            $family->main_wallet->deposit((float)$data['amount'] * 100, [
                'description'    => $description,
                'payment_method' => $data['payment_method'],
                'cashier_id'     => auth()->id(),
            ]);

            Log::info('Wallet topped up', [
                'user_id'     => $this->selectedUserId,
                'family_id'   => $family->id,
                'wallet_type' => 'main_wallet',
                'amount'      => $data['amount'],
            ]);

        } catch (ExceptionInterface $e) {
            Log::error('Failed to top up wallet', [
                'user_id'     => $this->selectedUserId,
                'family_id'   => $family->id,
                'wallet_type' => 'main_wallet',
                'amount'      => $data['amount'],
                'error'       => $e->getMessage(),
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
            ->body("{$data['amount']} was added to the main wallet.")
            ->success()
            ->send();

        $this->data = [];
        $this->refreshUserSearchForm();
        $this->form->fill();

        $this->resetTable();
    }

    public function clearSelectedUser(): void
    {
        $this->selectUser(null);
        $this->refreshUserSearchForm();
        $this->form->fill();
        $this->resetTable();

    }

    protected function getListeners(): array
    {
        return [
            'user-selected' => 'refreshForm',
        ];
    }
}
