<?php

namespace App\Filament\Resources\CelebrationResource\Pages;

use App\Filament\Resources\CelebrationResource;
use App\Models\Booking;
use App\Models\Cake;
use App\Models\Celebration;
use App\Models\CelebrationFeature;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemModifier;
use App\Models\Child;
use App\Models\Family;
use App\Models\MenuItem;
use App\Models\ModifierOption;
use App\Models\Package;
use App\Models\PartyInvitationTemplate;
use App\Models\Table;
use App\Models\Theme;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CelebrationPricingService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Log;
use Throwable;

class CreateCelebration extends CreateRecord
{
    protected static string $resource = CelebrationResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Parent')
                                    ->placeholder('Search by name, email or phone...')
                                    ->searchable()
                                    ->allowHtml()
                                    ->preload()
                                    ->searchDebounce(300)
                                    ->getSearchResultsUsing(function (string $search): array {
                                        if (empty($search) || strlen($search) < 2) {
                                            return [];
                                        }

                                        return User::query()
                                            ->where(function ($query) use ($search) {
                                                $query->where('email', 'ILIKE', "%{$search}%")
                                                    ->orWhereHas('profile', function ($q) use ($search) {
                                                        $q->where('first_name', 'ILIKE', "%{$search}%")
                                                            ->orWhere('last_name', 'ILIKE', "%{$search}%")
                                                            ->orWhere('phone_number', 'ILIKE', "%{$search}%");
                                                    });
                                            })
                                            ->with(['profile', 'family'])
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function($user) {
                                                $name = $user->profile->first_name . ' ' . $user->profile->last_name;
                                                $phone = $user->profile->phone_number;
                                                $family = $user->family ? $user->family->name : 'No Family';

                                                $label = "<div class='flex justify-between items-center'>";
                                                $label .= "<div>";
                                                $label .= "<div class='font-medium'>{$name}</div>";
                                                $label .= "<div class='text-sm text-gray-500'>{$phone}</div>";
                                                $label .= "</div>";
                                                $label .= "<div class='text-xs text-gray-400'>{$family}</div>";
                                                $label .= "</div>";

                                                return [$user->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $user = User::with(['profile', 'family'])->find($value);
                                        if (!$user) return '';

                                        $name = $user->profile->first_name . ' ' . $user->profile->last_name;
                                        $phone = $user->profile->phone_number;
                                        $family = $user->family ? $user->family->name : 'No Family';

                                        $label = "<div class='flex justify-between items-center'>";
                                        $label .= "<div>";
                                        $label .= "<div class='font-medium'>{$name}</div>";
                                        $label .= "<div class='text-sm text-gray-500'>{$phone}</div>";
                                        $label .= "</div>";
                                        $label .= "<div class='text-xs text-gray-400'>{$family}</div>";
                                        $label .= "</div>";

                                        return $label;
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $user = User::with('family')->find($state);
                                            if ($user && $user->family) {
                                                $set('family_id', $user->family->id);
                                                // Clear child selection when user changes
                                                $set('child_id', null);
                                            }
                                        } else {
                                            $set('family_id', null);
                                            $set('child_id', null);
                                        }
                                    }),

                                Select::make('family_id')
                                    ->label('Family')
                                    ->options(function (Get $get) {
                                        $userId = $get('user_id');
                                        if (!$userId) return [];

                                        $user = User::with('family')->find($userId);
                                        if (!$user || !$user->family) return [];

                                        return [$user->family->id => $user->family->name];
                                    })
                                    ->disabled()
                                    ->dehydrated(),

                                Select::make('child_id')
                                    ->label('Child')
                                    ->options(function (Get $get) {
                                        $userId = $get('user_id');
                                        if (!$userId) return [];

                                        $user = User::with('family.children')->find($userId);
                                        if (!$user || !$user->family) return [];

                                        return $user->family->children->pluck('first_name', 'id')->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // Any additional logic when child is selected
                                    })
                                    ->disabled(fn(Get $get) => !$get('user_id'))
                                    ->placeholder(fn(Get $get) => $get('user_id') ? 'Select a child' : 'Select parent first'),
                            ]),

                        Placeholder::make('family_info')
                            ->label('Family Information')
                            ->content(function (Get $get) {
                                $userId = $get('user_id');
                                if (!$userId) return 'Select a parent to see family information';

                                $user = User::with(['family.children'])->find($userId);
                                if (!$user || !$user->family) return 'No family information available';

                                $family = $user->family;
                                $walletBalance = $family->main_wallet ? $family->main_wallet->balance / 100 : 0;
                                $childrenCount = $family->children->count();

                                $html = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-3">';
                                $html .= '<div class="grid grid-cols-2 gap-4">';
                                $html .= '<div>';
                                $html .= '<p class="text-sm font-medium text-blue-900">Family: ' . $family->name . '</p>';
                                $html .= '<p class="text-sm text-blue-700">Children: ' . $childrenCount . '</p>';
                                $html .= '</div>';
                                $html .= '<div>';
                                $html .= '<p class="text-sm font-medium text-blue-900">Wallet Balance</p>';
                                $html .= '<p class="text-sm text-blue-700">AED ' . number_format($walletBalance, 2) . '</p>';
                                $html .= '</div>';
                                $html .= '</div>';
                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull()
                            ->visible(fn(Get $get) => !empty($get('user_id'))),
                    ]),

                Section::make('Package & Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('package_id')
                                    ->label('Package')
                                    ->options(Package::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                        if ($state) {
                                            $package = Package::find($state);
                                            if ($package) {
                                                $set('min_children', $package->min_children);
                                                if ($get('children_count') < $package->min_children) {
                                                    $set('children_count', $package->min_children);
                                                }
                                                $this->updatePricing($set, $get);
                                            }
                                        }
                                    }),

                                TextInput::make('min_children')
                                    ->label('Minimum Children')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('children_count')
                                    ->label('Number of Children')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $minChildren = $get('min_children');
                                        if ($minChildren && $state < $minChildren) {
                                            $set('children_count', $minChildren);
                                            Notification::make()
                                                ->title('Minimum children requirement')
                                                ->body("This package requires at least {$minChildren} children.")
                                                ->warning()
                                                ->send();
                                        }
                                        $this->updatePricing($set, $get);
                                    }),

                                TextInput::make('parents_count')
                                    ->label('Number of Parents')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, Get $get) => $this->updatePricing($set, $get)),
                            ]),
                    ]),

                Section::make('Date & Time Selection')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('celebration_date_only')
                                    ->label('Celebration Date')
                                    ->minDate(now()->addDays(5)) // Block dates before 5 days from now
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        // Clear time slot when date changes
                                        $set('celebration_time_slot', null);
                                        $set('celebration_date', null);
                                    })
                                    ->dehydrated(false), // Don't save this field directly

                                Select::make('celebration_time_slot')
                                    ->label('Available Time Slots')
                                    ->options(function (Get $get) {
                                        $date = $get('celebration_date_only');
                                        $packageId = $get('package_id');
                                        $childrenCount = $get('children_count');

                                        if (!$date || !$packageId || !$childrenCount) {
                                            return [];
                                        }

                                        try {
                                            $package = Package::find($packageId);
                                            if (!$package) return [];

                                            $bookingService = app(BookingService::class);
                                            $slots = $bookingService->getAvailableTimeSlots(
                                                Carbon::parse($date)->format('Y-m-d'),
                                                $package,
                                                $childrenCount
                                            );

                                            if (empty($slots)) {
                                                return [];
                                            }

                                            $options = [];
                                            foreach ($slots as $slot) {
                                                $startTime = Carbon::parse($slot['start_time'])->format('H:i');
                                                $endTime = Carbon::parse($slot['end_time'])->format('H:i');
                                                $duration = $package->duration_hours;

                                                $options[$slot['start_time']] = "{$startTime} - {$endTime} ({$duration}h)";
                                            }

                                            return $options;
                                        } catch (Exception $e) {
                                            return [];
                                        }
                                    })
                                    ->placeholder(function (Get $get) {
                                        $date = $get('celebration_date_only');
                                        $packageId = $get('package_id');
                                        $childrenCount = $get('children_count');

                                        if (!$date) {
                                            return 'Please select a date first';
                                        }
                                        if (!$packageId) {
                                            return 'Please select a package first';
                                        }
                                        if (!$childrenCount) {
                                            return 'Please set children count first';
                                        }

                                        return 'Loading available slots...';
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $date = $get('celebration_date_only');

                                        if ($date && $state) {
                                            // Combine date and time to create the full datetime
                                            $dateTime = Carbon::parse($date)->format('Y-m-d') . ' ' . Carbon::parse($state)->format('H:i:s');
                                            $set('celebration_date', $dateTime);

                                            // Update pricing when time slot is selected
                                            $this->updatePricing($set, $get);
                                        }
                                    })
                                    ->disabled(fn(Get $get) => !$get('celebration_date_only') || !$get('package_id') || !$get('children_count'))
                                    ->dehydrated(false), // Don't save this field directly
            ]),

        Placeholder::make('slot_info')
            ->label('Slot Information')
            ->content(function (Get $get) {
                $date = $get('celebration_date_only');
                $selectedSlot = $get('celebration_time_slot');

                if (!$date) {
                    return 'Please select a date to see available time slots';
                }

                if (!$selectedSlot) {
                    return 'Date selected: ' . Carbon::parse($date)->format('M j, Y') . '. Please choose a time slot above.';
                }

                // Show selected slot information
                return 'Selected: ' . Carbon::parse($date)->format('M j, Y') . ' at ' . Carbon::parse($selectedSlot)->format('H:i');
            })
            ->columnSpanFull(),

        // Hidden field to store the actual datetime
        Hidden::make('celebration_date')
            ->dehydrated(true),
    ]),

                Section::make('Theme & Decorations')
                    ->schema([
                        Select::make('theme_id')
                            ->label('Theme')
                            ->options(Theme::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => $this->updatePricing($set, $get)),
                    ]),

                Section::make('Cake Selection')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('cake_id')
                                    ->label('Cake Type')
                                    ->options(Cake::pluck('type', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if ($state) {
                                            $cake = Cake::find($state);
                                            if ($cake) {
                                                $set('cake_price_per_kg', $cake->price_per_kg);
                                            }
                                        }
                                        $this->updatePricing($set, $get);
                                    }),

                                TextInput::make('cake_weight')
                                    ->label('Cake Weight (kg)')
                                    ->numeric()
                                    ->step(0.5)
                                    ->suffix('kg')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, Get $get) => $this->updatePricing($set, $get)),

                                TextInput::make('cake_price_per_kg')
                                    ->label('Price per KG')
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Section::make('Menu Selection')
                    ->schema([
                        Repeater::make('menu_items')
                            ->label('Menu Items')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('menu_item_id')
                                            ->label('Menu Item')
                                            ->options(MenuItem::pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                if ($state) {
                                                    $menuItem = MenuItem::find($state);
                                                    if ($menuItem) {
                                                        $set('price', $menuItem->price);
                                                    }
                                                }
                                            }),

                                        Select::make('audience')
                                            ->label('For')
                                            ->options([
                                                'children' => 'Children',
                                                'parents' => 'Parents',
                                            ])
                                            ->required()
                                            ->live(),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required()
                                            ->live(onBlur: true),

                                        TextInput::make('price')
                                            ->label('Price per Item')
                                            ->prefix('AED')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),

                                CheckboxList::make('modifier_option_ids')
                                    ->label('Modifiers')
                                    ->options(function (Get $get) {
                                        $menuItemId = $get('menu_item_id');
                                        if (!$menuItemId) return [];

                                        try {
                                            $menuItem = MenuItem::with('modifierGroups.options')->find($menuItemId);
                                            if (!$menuItem) return [];

                                            $options = [];
                                            foreach ($menuItem->modifierGroups as $group) {
                                                foreach ($group->options as $option) {
                                                    $options[$option->id] = $group->title . ': ' . $option->name . ' (+AED ' . number_format($option->price, 2) . ')';
                                                }
                                            }

                                            return $options;
                                        } catch (Exception $e) {
                                            return [];
                                        }
                                    })
                                    ->columns(2)
                                    ->columnSpanFull(),

                                TextInput::make('child_name')
                                    ->label('Child Name (for individual orders)')
                                    ->visible(fn(Get $get) => $get('audience') === 'children')
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Add Menu Item')
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => $this->updatePricing($set, $get))
                            ->columnSpanFull(),
                    ]),

                Section::make('Additional Features')
                    ->schema([
                        CheckboxList::make('celebration_features')
                            ->label('Additional Features')
                            ->options(function () {
                                return CelebrationFeature::all()->mapWithKeys(function ($feature) {
                                    return [$feature->id => $feature->title . ' (AED ' . number_format($feature->price, 2) . ')'];
                                });
                            })
                            ->descriptions(function () {
                                return CelebrationFeature::pluck('description', 'id')->map(fn($desc) => $desc ?: '')->toArray();
                            })
                            ->columns(2)
                            ->live()
                            ->afterStateUpdated(fn(Set $set, Get $get) => $this->updatePricing($set, $get)),
                    ]),

//                Section::make('Slideshow Images')
//                    ->schema([
//                        SpatieMediaLibraryFileUpload::make('slideshow_images')
//                            ->label('Slideshow Images')
//                            ->collection('slideshow_images')
//                            ->image()
//                            ->multiple()
//                            ->maxFiles(20)
//                            ->reorderable()
//                            ->columnSpanFull(),
//                    ]),
//
//                Section::make('Party Invitation')
//                    ->schema([
//                        Select::make('invitation_template_id')
//                            ->label('Invitation Template')
//                            ->options(PartyInvitationTemplate::pluck('id', 'id'))
//                            ->searchable(),
//                    ]),

                Section::make('Pricing Summary')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('package_price_display')
                                    ->label('Package Price')
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('cake_total_display')
                                    ->label('Cake Total')
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('menu_total_display')
                                    ->label('Menu Total')
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('features_total_display')
                                    ->label('Features Total')
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->prefix('AED')
                                    ->disabled()
                                    ->afterStateHydrated(function ($component, $state) {
                                        if (is_numeric($state)) {
                                            $component->state($state / 100);
                                        }
                                    })
                                    ->dehydrateStateUsing(fn($state) => $state * 100),

                                TextInput::make('min_amount')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->step(1),
                            ]),

                        Actions::make([
                            Action::make('calculate_pricing')
                                ->label('Recalculate Pricing')
                                ->icon('heroicon-o-calculator')
                                ->color('primary')
                                ->action(function (Set $set, Get $get) {
                                    $this->updatePricing($set, $get);
                                    Notification::make()
                                        ->title('Pricing Updated')
                                        ->success()
                                        ->send();
                                }),
                        ])->columnSpanFull(),
                    ]),

                Section::make('Additional Settings')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Toggle::make('completed')
                                    ->label('Mark as Completed')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }

    protected function updatePricing(Set $set, Get $get): void
    {
        $packageId = $get('package_id');
        $cakeId = $get('cake_id');
        $cakeWeight = $get('cake_weight');
        $celebrationDate = $get('celebration_date');
        $childrenCount = $get('children_count');
        $parentsCount = $get('parents_count');
        $menuItems = $get('menu_items') ?? [];
        $featuresIds = $get('celebration_features') ?? [];

        if (!$packageId || !$celebrationDate || !$childrenCount) {
            return;
        }

        try {
            $package = Package::find($packageId);
            if (!$package) return;

            $celebrationDateCarbon = Carbon::parse($celebrationDate);

            // Calculate package price
            $packagePrice = $celebrationDateCarbon->isBusinessWeekend()
                ? $package->weekend_price
                : $package->weekday_price;

            $packagePrice *= $childrenCount;
            $set('package_price_display', number_format($packagePrice, 2));

            // Calculate cake price
            $cakeTotal = 0;
            if ($cakeId && $cakeWeight) {
                $cake = Cake::find($cakeId);
                if ($cake) {
                    $cakeTotal = $cake->price_per_kg * $cakeWeight;
                }
            }
            $set('cake_total_display', number_format($cakeTotal, 2));

            // Calculate menu price
            $menuTotal = 0;
            foreach ($menuItems as $item) {
                if ($item['audience'] === 'parents' && isset($item['menu_item_id']) && isset($item['quantity'])) {
                    $menuItem = MenuItem::find($item['menu_item_id']);
                    if ($menuItem) {
                        $itemTotal = $menuItem->price * $item['quantity'];

                        // Add modifier prices
                        if (isset($item['modifier_option_ids']) && is_array($item['modifier_option_ids'])) {
                            foreach ($item['modifier_option_ids'] as $modifierId) {
                                $modifier = ModifierOption::find($modifierId);
                                if ($modifier) {
                                    $itemTotal += $modifier->price * $item['quantity'];
                                }
                            }
                        }

                        $menuTotal += $itemTotal;
                    }
                }
            }
            $set('menu_total_display', number_format($menuTotal, 2));

            // Calculate features price
            $featuresTotal = 0;
            if (!empty($featuresIds)) {
                $features = CelebrationFeature::whereIn('id', $featuresIds)->get();
                $featuresTotal = $features->sum('price');
            }
            $set('features_total_display', number_format($featuresTotal, 2));

            // Calculate total
            $totalAmount = $packagePrice + $cakeTotal + $menuTotal + $featuresTotal;
            $set('total_amount', $totalAmount);

        } catch (Exception $e) {
            Notification::make()
                ->title('Pricing calculation error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['current_step'] = 10; // Mark as completed all steps

        // Convert amounts from display format to cents
        if (isset($data['total_amount'])) {
            $data['total_amount'] = $data['total_amount'] * 100;
        }
        if (isset($data['min_amount'])) {
            $data['min_amount'] = $data['min_amount'] * 100;
        }

        $data['paid_amount'] = 0; // No payment initially

        // Ensure celebration_date is properly formatted
        if (isset($data['celebration_date'])) {
            $data['celebration_date'] = Carbon::parse($data['celebration_date'])->format('Y-m-d H:i:s');
        }

        // Remove the helper fields that shouldn't be saved
        unset($data['celebration_date_only']);
        unset($data['celebration_time_slot']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($record, $data) {
                // Create booking and assign tables
                $this->createBookings($record, $data);

                // Create cart and menu items
                $this->createCart($record, $data);

                // Attach features
                $this->attachFeatures($record, $data);

                // Handle slideshow images (handled automatically by Spatie Media Library)

                // Create invitation if template selected
                $this->createInvitation($record, $data);

                $this->calculateAndSetTotalAmount($record);
            });

            Notification::make()
                ->title('Celebration created successfully!')
                ->body('All components have been set up for the celebration.')
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error creating celebration')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Unexpected error')
                ->body('An unexpected error occurred while creating the celebration: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @throws Exception
     */
    protected function createBookings(Celebration $celebration, array $data): void
    {
        if (!isset($data['package_id'], $data['children_count'], $data['celebration_date'])) {
            return;
        }

        $package = Package::find($data['package_id']);
        $childrenCount = $data['children_count'];
        $celebrationDate = Carbon::parse($data['celebration_date']);

        if (!$package) {
            throw new Exception('Package not found');
        }

        try {
            $bookingService = app(BookingService::class);

            $slots = $bookingService->getAvailableTimeSlots(
                $celebrationDate->format('Y-m-d'),
                $package,
                $childrenCount
            );

            if (empty($slots)) {
                throw new Exception('No available slots for the selected date and children count');
            }

            $selectedSlot = $slots[0];
            $selectedTime = $celebrationDate->format('H:i');

            foreach ($slots as $slot) {
                if (Carbon::parse($slot['start_time'])->format('H:i') === $selectedTime) {
                    $selectedSlot = $slot;
                    break;
                }
            }

            $bookingData = [
                'user_id' => $celebration->user_id,
                'celebration_id' => $celebration->id,
                'package_id' => $celebration->package_id,
                'child_name' => $celebration->child->first_name ?? 'Celebration',
                'children_count' => $childrenCount,
                'start_time' => $selectedSlot['start_time'],
                'special_requests' => $data['special_requests'] ?? null,
            ];

            $booking = $bookingService->createBooking($bookingData);

            Log::info('Booking created successfully via BookingService', [
                'booking_id' => $booking->id,
                'celebration_id' => $celebration->id,
                'tables' => $booking->tables->pluck('name')->toArray()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create bookings via BookingService', [
                'error' => $e->getMessage(),
                'celebration_id' => $celebration->id,
                'data' => $data
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('Unexpected error creating bookings', [
                'error' => $e->getMessage(),
                'celebration_id' => $celebration->id,
                'data' => $data
            ]);
            throw new Exception('An unexpected error occurred while creating bookings: ' . $e->getMessage());
        }
    }

    protected function createCart(Celebration $celebration, array $data): void
    {
        if (empty($data['menu_items'])) {
            return;
        }

        $cart = Cart::create([
            'celebration_id' => $celebration->id,
            'total_price' => 0,
        ]);

        $totalPrice = 0;

        foreach ($data['menu_items'] as $menuItemData) {
            if (!isset($menuItemData['menu_item_id'], $menuItemData['audience'], $menuItemData['quantity'])) {
                continue;
            }

            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'menu_item_id' => $menuItemData['menu_item_id'],
                'audience' => $menuItemData['audience'],
                'quantity' => $menuItemData['quantity'],
                'child_name' => $menuItemData['child_name'] ?? null,
            ]);

            if (!empty($menuItemData['modifier_option_ids'])) {
                foreach ($menuItemData['modifier_option_ids'] as $modifierId) {
                    CartItemModifier::create([
                        'cart_item_id' => $cartItem->id,
                        'modifier_option_id' => $modifierId,
                    ]);
                }
            }

            if ($menuItemData['audience'] === 'parents') {
                $menuItem = MenuItem::find($menuItemData['menu_item_id']);
                if ($menuItem) {
                    $itemPrice = $menuItem->price * $menuItemData['quantity'];

                    if (!empty($menuItemData['modifier_option_ids'])) {
                        $modifierPrice = ModifierOption::whereIn('id', $menuItemData['modifier_option_ids'])
                                ->sum('price') * $menuItemData['quantity'];
                        $itemPrice += $modifierPrice;
                    }

                    $totalPrice += $itemPrice;
                }
            }
        }

        $cart->update(['total_price' => $totalPrice]);
    }

    protected function attachFeatures($celebration, array $data): void
    {
        if (!empty($data['celebration_features'])) {
            $celebration->features()->attach($data['celebration_features']);
        }
    }

    protected function createInvitation($celebration, array $data): void
    {
        // Invitation creation logic would go here
        // This depends on your specific Invite model and invitation generation process
    }

    protected function calculateAndSetTotalAmount(Celebration $celebration): void
    {
        $celebration->load([
            'package',
            'cake',
            'features',
            'cart.items.menuItem',
            'cart.items.modifiers.modifierOption'
        ]);

        $pricingService = app(CelebrationPricingService::class);
        $pricing = $pricingService->calculateTotalPrice($celebration);

        $celebration->update([
            'total_amount' => $pricing['total_cost']
        ]);

        \Log::info('Total amount calculated and set', [
            'celebration_id' => $celebration->id,
            'total_amount' => $pricing['total_cost'],
            'breakdown' => $pricing['breakdown']
        ]);
    }
}
