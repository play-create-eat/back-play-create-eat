<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Data\Products\PassPurchaseData;
use App\Data\Products\PassPurchaseProductData;
use App\Exceptions\InsufficientBalanceException;
use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Concerns\HasUserSearchForm;
use App\Models\Child;
use App\Models\Product;
use App\Models\User;
use App\Services\PassService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Objects\Cart;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Carbon\Carbon;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Throwable;

class CashierTickets extends Page implements HasForms
{
    use InteractsWithForms;
    use HasGlobalUserSearch;
    use HasUserSearchForm;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static string $view = 'filament.clusters.cashier.pages.tickets';
    protected static ?string $navigationLabel = 'Tickets Purchase';

    protected static ?int $navigationSort = 2;
    public ?array $data = [
        'tickets' => [],
    ];
    public ?Collection $products = null;
    #[Url]
    public ?string $order = null;
    public array $passes = [];
    public string $step = 'checkout';
    protected $queryString = [
        'order' => ['except' => null],
    ];

    public static function canAccess(): bool
    {
        return auth()->guard('admin')->user()->can('viewTickets')
            && auth()->guard('admin')->user()->can('buyTickets');
    }

    public function mount(): void
    {
        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }

        $this->mountHasUserSearchForm();
        $this->passes = $this->order ? Cache::get("cashier.order.{$this->order}", []) : [];
        $this->products = Product::available()->with(['features'])->get()->keyBy('id');

        $this->form->fill();
    }

    public function refreshForm(): void
    {
        Log::info('Refreshing tickets form', [
            'selectedUserId' => $this->selectedUserId,
            'hasUser' => (bool)$this->selectedUser,
        ]);

        if ($this->selectedUserId) {
            $this->selectedUser = User::with([
                'profile',
                'family',
                'family.children'
            ])->find($this->selectedUserId);
        }

        $this->data = [
            'tickets' => []
        ];

        $this->refreshUserSearchForm();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        if (!$this->selectedUser) {
            return $form
                ->schema([
                    $this->getUserSearchField()
                        ->columnSpanFull(),
                ])
                ->statePath('data');
        }

        return $form
            ->schema([
                Repeater::make('tickets')
                    ->schema([
                        Select::make('child_id')
                            ->label('Child')
                            ->native(false)
                            ->live()
                            ->dehydrated()
                            ->options(function () {
                                if (!$this->selectedUser || !$this->selectedUser->family) {
                                    return [];
                                }

                                return $this->selectedUser->family->children
                                    ->mapWithKeys(fn(Child $child) => [
                                        $child->id => "$child->first_name $child->last_name"
                                    ])
                                    ->toArray();
                            })
                            ->exists(table: Child::class, column: 'id')
                            ->required()
                        ,
                        DatePicker::make('activation_date')
                            ->label('Date')
                            ->native(false)
                            ->live()
                            ->default(today())
                            ->minDate(today()->startOfDay())
                            ->weekStartsOnMonday()
                            ->displayFormat('d.m.Y')
                            ->rules(['required', 'date', 'after_or_equal:today'])
                            ->required()
                        ,
                        Select::make('product_id')
                            ->label('Ticket')
                            ->native(false)
                            ->allowHtml()
                            ->options(fn(Get $get) => $this->getProductOptions($get('activation_date')))
                            ->disabled(fn(Get $get) => blank($get('child_id')))
                            ->exists(table: Product::class, column: 'id')
                            ->required()
                            ->columnSpan(2)
                    ])
                    ->columns()
                    ->addActionLabel('Add ticket')
                    ->minItems(1)
                    ->disabled(fn() => !$this->selectedUser)
                    ->visible(fn() => (bool)$this->selectedUser)
                    ->live()
                    ->columnSpanFull()
            ])
            ->statePath('data');
    }

    protected function getProductOptions(string $activationDate = null): array
    {
        if ($this->products) {
            $date = $activationDate ? Carbon::parse($activationDate) : today();
            return $this->products
                ->mapWithKeys(function (Product $product) use ($date) {
                    return [
                        "{$product->id}" => view('filament.clusters.cashier.components.ticket-option', [
                            'name' => $product->name,
                            'features' => $product->features->pluck('name')->toArray(),
                            'price' => app(FormatterServiceInterface::class)->floatValue($product->getFinalPrice($date), 2),
                        ])->render()
                    ];
                })
                ->toArray();
        }
        return [];
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            $tickets = $data['tickets'] ?? [];

            if (empty($tickets)) {
                Notification::make()
                    ->title('No tickets added')
                    ->body('Please add at least one ticket to continue.')
                    ->danger()
                    ->send();
                return;
            }

            $cart = app(Cart::class);

            if (!$this->selectedUser) {
                throw new Exception('No user selected');
            }

            $client = User::with(['family.children', 'family.wallets'])->find($this->selectedUser->id);

            if (!$client || !$client->family) {
                throw new Exception('User has no associated family');
            }

            if (!$client->family->relationLoaded('wallets')) {
                $client->family->load('wallets');
            }

            foreach ($tickets as $ticket) {
                /** @var Product $product */
                $product = $this->products->get($ticket['product_id'] ?? null);

                if (!$product) {
                    throw new Exception('Invalid product selected');
                }

                $date = Carbon::parse($ticket['activation_date']);
                $productPrice = $product->getFinalPrice($date);
                $cart = $cart->withItem($product, pricePerItem: $productPrice);
            }

            $totalPrice = $cart->getTotal($client->family);

            throw_unless($client->family->canWithdraw($totalPrice),
                new InsufficientBalanceException(
                    amount: $totalPrice,
                    balance: $client->family->main_wallet->balance,
                )
            );

            $cashierUser = Filament::auth()->user();
            $orderId = (string)Str::uuid7(time: now());
            $products = [];

            foreach ($tickets as $ticket) {
                $products[] = [
                    'product_id' => $ticket['product_id'],
                    'child_id' => $ticket['child_id'],
                    'date' => $ticket['activation_date'],
                ];
            }

            $passes = app(PassService::class)->purchaseMultiple(
                user: $client,
                data: PassPurchaseData::from([
                    'products' => PassPurchaseProductData::collect($products),
                ]),
                meta: [
                    'cashier_order_id' => $orderId,
                    'cashier_user_id' => $cashierUser->id,
                ],
            );

            DB::commit();
            Cache::put("cashier.order.$orderId", $passes, now()->addDays(2));

            $this->passes = $passes;
            $this->order = $orderId;
            $this->step = 'fulfillment';

            $this->data = [
                'tickets' => []
            ];

            Notification::make()
                ->title('Tickets purchased successfully')
                ->success()
                ->send();
        } catch (InsufficientBalanceException $e) {
            DB::rollBack();
            Notification::make()
                ->title('Insufficient balance')
                ->body("The family doesn't have enough funds. Required: " . number_format($e->getAmount() / 100, 2) . ", Available: " . number_format($e->getBalance() / 100, 2))
                ->danger()
                ->send();
        } catch (ExceptionInterface $e) {
            DB::rollBack();
            Notification::make()
                ->title('Failed to purchase tickets')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('An error occurred')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function back(): void
    {
        $this->order = null;
        $this->passes = [];
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
