<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Exceptions\InsufficientBalanceException;
use App\Filament\Clusters\Cashier;
use App\Models\Child;
use App\Models\Product;
use App\Models\User;
use App\Services\PassService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Objects\Cart;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Carbon\Carbon;
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

class CashierTickets extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static string $view = 'filament.clusters.cashier.pages.tickets';

    protected static ?string $navigationLabel = 'Tickets';
    protected static ?string $title = 'Tickets';

    protected $queryString = [
        'order' => ['except' => null],
    ];

    public ?array $data = [
        'user_id' => null,
        'activation_date' => null,
        'tickets' => [],
    ];

    public ?Collection $products = null;
    public ?User $client = null;

    #[Url]
    public ?string $order = null;
    public array $passes = [];
    public string $step = 'checkout'; // checkout | fulfillment

    public function mount(): void
    {
        $this->passes = $this->order ? Cache::get("cashier.order.{$this->order}", []) : [];
        $this->form->fill();
        $this->products = Product::available()->with(['features'])->get()->keyBy('id');
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
                ,
                Repeater::make('tickets')
                    ->schema([
                        Select::make('child_id')
                            ->label('Child')
                            ->native(false)
                            ->live()
                            ->options(function (Get $get) {
                                $userId = $get('../../user_id');

                                if (!$userId) {
                                    return [];
                                }

                                $user = User::with('family.children')->find($userId);

                                if ($user) {
                                    return $user->family->children
                                        ->mapWithKeys(fn(Child $child) => [$child->id => "{$child->first_name} {$child->last_name}"])
                                        ->toArray();
                                }

                                return [];
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
                    ->columns(2)
                    ->addActionLabel('Add ticket')
                    ->minItems(1)
                    ->disabled(fn(Get $get): bool => blank($get('user_id')))
                    ->reactive()
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            $tickets = $data['tickets'] ?? [];
            $cart = app(Cart::class);
            $client = User::with('family')->findOrFail($data['user_id']);

            foreach ($tickets as $ticket) {
                /** @var Product $product */
                $product = $this->products->get($ticket['product_id'] ?? null);
                $date = Carbon::parse($ticket['activation_date']);

                if ($product) {
                    $productPrice = $product->getFinalPrice($date);
                    $cart = $cart->withItem($product, pricePerItem: $productPrice);
                }
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

            foreach ($tickets as $ticket) {
                $passes[] = app(PassService::class)->purchase(
                    user: $client,
                    child: $children->get($ticket['child_id']),
                    product: $this->products->get($ticket['product_id']),
                    loyaltyPointAmount: 0,
                    activationDate: Carbon::parse($ticket['activation_date']),
                    meta: $orderMeta
                );
            }

            DB::commit();
            Cache::put("cashier.order.{$orderId}", $passes, now()->addDays(2));

            $this->passes = $passes;
            $this->order = $orderId;
        } catch (ExceptionInterface $e) {
            DB::rollBack();
            Notification::make()
                ->title('Failed to purchase tickets.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function back()
    {
        $this->order = null;
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
