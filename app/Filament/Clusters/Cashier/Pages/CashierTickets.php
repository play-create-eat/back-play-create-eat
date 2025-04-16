<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Models\Family;
use App\Models\User;
use Bavix\Wallet\Objects\Cart;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class CashierTickets extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Cashier::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static string $view = 'filament.clusters.cashier.pages.tickets';

    protected static ?string $navigationLabel = 'Tickets';
    protected static ?string $title = 'Tickets';

    public ?string $searchTerm = null;
    public ?string $duration = null;
    public array $features = [];

    public ?User $selectedUser = null;

    public ?Carbon $transactionDate = null;

    public ?Cart $cart = null;

    public function mount(): void
    {
        $this->form->fill();

        $this->transactionDate = Carbon::today();
        $this->features = [];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('family_id')
                    ->label('Family')
                    ->options(Family::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                DatePicker::make('activation_date')
                    ->label('Activation date')
                    ->minDate(today())
                    ->weekStartsOnMonday()
                ,
                Repeater::make('tickets')
                    ->schema([
                        TextInput::make('child_id')
                            ->label('Child')
                            ->required(),
                        Select::make('product_id')
                            ->label('Ticket type')
                            ->options([
                                'member' => 'Member',
                                'administrator' => 'Administrator',
                                'owner' => 'Owner',
                            ])
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add ticket')
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $family = Family::findOrFail($data['family_id']);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

//    public function render()
//    {
//        $products = Product::available()
//            ->with(['features'])
//            ->when($this->duration, function ($query) {
//                $query->whereIn('duration_time', $this->duration);
//            })
//            ->when(count($this->features) > 0, function ($query) {
//                $query->whereHas('features', function ($query) {
//                    $query->whereIn('id', $this->features);
//                });
//            })
//            ->limit(50)
//            ->get();
//
//        return view(static::$view, [
//            'products' => $products,
//        ]);
//    }

    public function searchUser()
    {

        $term = $this->searchTerm;

        if (!$term) {
            $this->selectedUser = null;
            return;
        }


    }
}
