<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Concerns\HasUserSearchForm;
use App\Models\Child;
use App\Models\PassPackage;
use App\Models\User;
use App\Services\ProductPackageService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Throwable;

class CashierTicketRedeem extends Page implements HasForms
{
    use InteractsWithForms;
    use HasGlobalUserSearch;
    use HasUserSearchForm;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static string $view = 'filament.clusters.cashier.pages.ticket-redeem';
    protected static ?string $navigationLabel = 'Ticket Redeem';

    protected static ?int $navigationSort = 2;
    public ?array $data = [
        'tickets' => [],
    ];

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
        $this->passes = $this->order ? Cache::get("cashier.redeem.{$this->order}", []) : [];
        $this->form->fill();
    }

    public function refreshForm(): void
    {
        Log::info('Refreshing tickets package redeem form', [
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
            'tickets' => [],
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
                        Select::make('pass_package_id')
                            ->label('Ticket Package')
                            ->native(false)
                            ->live()
                            ->dehydrated()
                            ->options(function (Get $get) {
                                $childId = $get('child_id');

                                if (!$childId) {
                                    return [];
                                }

                                return PassPackage::available()
                                    ->with(['productPackage'])
                                    ->where('child_id', $childId)
                                    ->get()
                                    ->mapWithKeys(function (PassPackage $passPackage) {
                                        $productPackage = $passPackage->productPackage;
                                        return [
                                            $passPackage->id => "{$productPackage->name} (x{$passPackage->quantity})"
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->disabled(fn(Get $get) => !$get('child_id'))
                            ->exists(table: PassPackage::class, column: 'id')
                            ->required()
                        ,
                    ])
                    ->columns()
                    ->deletable(false)
                    ->minItems(1)
                    ->maxItems(1)
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
                    ->title('No packages added')
                    ->body('Please add at least one package to continue.')
                    ->danger()
                    ->send();
                return;
            }

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

            $cashierUser = Filament::auth()->user();
            $orderId = (string)Str::uuid7(time: now());
            $orderMeta = [
                'cashier_order_id' => $orderId,
                'cashier_user_id' => $cashierUser->id,
            ];

            $passPackages = PassPackage::available()
                ->whereIn('id', Arr::pluck($tickets, 'pass_package_id'))
                ->get()
                ->keyBy('id');

            $children = $client->family->children()
                ->whereIn('id', Arr::pluck($tickets, 'child_id'))
                ->get()
                ->keyBy('id');

            $passes = [];
            $productPackageService = app(ProductPackageService::class);

            foreach ($tickets as $ticket) {
                $childId = $ticket['child_id'];
                $child = $children->get($childId);

                if (!$child) {
                    throw new Exception("Child with ID {$childId} not found");
                }

                $passPackageId = $ticket['pass_package_id'];
                $passPackage = $passPackages->get($passPackageId);

                if (!$child) {
                    throw new Exception("Pass package with ID {$passPackageId} not found");
                }

                $passes[] = $productPackageService->redeem(
                    passPackage: $passPackage,
                    meta: $orderMeta
                );
            }

            DB::commit();
            Cache::put("cashier.redeem.$orderId", $passes, now()->addDays(2));

            $this->passes = $passes;
            $this->order = $orderId;
            $this->step = 'fulfillment';

            $this->data = [
                'tickets' => []
            ];

            Notification::make()
                ->title('Ticket redeem successfully')
                ->success()
                ->send();
        } catch (ExceptionInterface $e) {
            DB::rollBack();
            Notification::make()
                ->title('Failed to redeem tickets')
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
