<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Exceptions\InsufficientBalanceException;
use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Concerns\HasUserSearchForm;
use App\Models\Child;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Models\User;
use App\Services\PassService;
use App\Services\ProductPackageService;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Throwable;

class CashierTicketPackages extends Page implements HasForms
{
    use InteractsWithForms;
    use HasGlobalUserSearch;
    use HasUserSearchForm;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static string $view = 'filament.clusters.cashier.pages.ticket-packages';
    protected static ?string $navigationLabel = 'Ticket Packages';

    protected static ?int $navigationSort = 2;
    public ?array $data = [
        'tickets' => [],
    ];

    public ?Collection $productPackages = null;

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
        $this->productPackages = ProductPackage::available()->get()->keyBy('id');
        $this->form->fill();
    }

    public function refreshForm(): void
    {
        Log::info('Refreshing tickets package form', [
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
            'packages' => [],
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
                Repeater::make('packages')
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
                        Select::make('product_package_id')
                            ->label('Package')
                            ->native(false)
                            ->allowHtml()
                            ->options(fn(Get $get) => $this->getProductPackageOptions())
                            ->disabled(fn(Get $get) => blank($get('child_id')))
                            ->exists(table: ProductPackage::class, column: 'id')
                            ->required()
                            ->columnSpan(2)
                    ])
                    ->columns()
                    ->addActionLabel('Add package')
                    ->minItems(1)
                    ->disabled(fn() => !$this->selectedUser)
                    ->visible(fn() => (bool)$this->selectedUser)
                    ->live()
                    ->columnSpanFull()
            ])
            ->statePath('data');
    }

    protected function getProductPackageOptions(): array
    {
        if ($this->productPackages) {
            return $this->productPackages
                ->mapWithKeys(function (ProductPackage $productPackage) {
                    return [
                        "{$productPackage->id}" => view('filament.clusters.cashier.components.ticket-package-option', [
                            'name' => $productPackage->name,
                            'price' => app(FormatterServiceInterface::class)->floatValue($productPackage->getFinalPrice(), 2),
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

            $packages = $data['packages'] ?? [];

            if (empty($packages)) {
                Notification::make()
                    ->title('No packages added')
                    ->body('Please add at least one package to continue.')
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

            foreach ($packages as $package) {
                /** @var ProductPackage $productPackage */
                $productPackage = $this->productPackages->get($package['product_package_id'] ?? null);

                if (!$productPackage) {
                    throw new Exception('Invalid package selected');
                }

                $cart = $cart->withItem($productPackage, pricePerItem: $productPackage->getFinalPrice());
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
                ->whereIn('id', Arr::pluck($packages, 'child_id'))
                ->get()
                ->keyBy('id');

            $productPackageService = app(ProductPackageService::class);

            foreach ($packages as $package) {
                $childId = $package['child_id'];
                $child = $children->get($childId);

                if (!$child) {
                    throw new Exception("Child with ID {$childId} not found");
                }

                $productPackage = $this->productPackages->get($package['product_package_id']);

                $productPackageService->purchase(
                    user: $client,
                    child: $child,
                    productPackage: $productPackage,
                    meta: $orderMeta
                );
            }

            DB::commit();
            
            Notification::make()
                ->title('Packages purchased successfully')
                ->success()
                ->send();

            redirect(request()->header('Referer'));
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
                ->title('Failed to purchase packages')
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
