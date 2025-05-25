<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Exceptions\InsufficientBalanceException;
use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Models\Child;
use App\Models\Product;
use App\Models\User;
use App\Services\PassService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Objects\Cart;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Throwable;

class CashierTickets extends Page implements HasForms
{
    use InteractsWithForms;
    use HasGlobalUserSearch;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static string $view = 'filament.clusters.cashier.pages.tickets';

    protected static ?string $navigationLabel = 'Tickets Purchase';
    protected static ?string $title = 'Tickets Purchase';

    protected $queryString = [
        'order' => ['except' => null],
    ];

    public ?array $data = [
        'tickets' => [],
    ];

    public ?Collection $products = null;

    #[Url]
    public ?string $order = null;
    public array $passes = [];
    public string $step = 'checkout';

    public function mount(): void
    {
        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }

        $this->passes = $this->order ? Cache::get("cashier.order.{$this->order}", []) : [];
        $this->products = Product::available()->with(['features'])->get()->keyBy('id');

        $this->form->fill();
    }

    protected function getListeners(): array
    {
        return [
            'user-selected' => 'refreshForm',
        ];
    }

    public function refreshForm(): void
    {
        $this->data = [
            'tickets' => []
        ];

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getUserSearchField()
                    ->hiddenOn('edit')
                    ->visible(fn() => !$this->selectedUser)
                    ->columnSpanFull(),

                Repeater::make('tickets')
                    ->schema([
                        Select::make('child_id')
                            ->label('Child')
                            ->native(false)
                            ->live()
                            ->dehydrated(true)
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
                        ,
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
            $orderMeta = [
                'cashier_order_id' => $orderId,
                'cashier_user_id' => $cashierUser->id,
            ];

            $children = $client->family->children()
                ->whereIn('id', Arr::pluck($tickets, 'child_id'))
                ->get()
                ->keyBy('id');

            $passes = [];
            $passService = app(PassService::class);

            foreach ($tickets as $ticket) {
                $childId = $ticket['child_id'];
                $child = $children->get($childId);

                if (!$child) {
                    throw new Exception("Child with ID {$childId} not found");
                }

                $passes[] = $passService->purchase(
                    user: $client,
                    child: $child,
                    product: $this->products->get($ticket['product_id']),
                    loyaltyPointAmount: 0,
                    activationDate: Carbon::parse($ticket['activation_date']),
                    meta: $orderMeta
                );
            }

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
                ->body("The family doesn't have enough funds. Required: " . $e->getAmount() . ", Available: " . $e->getBalance())
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
}
