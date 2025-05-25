<?php

namespace App\Filament\Clusters\Cashier\Pages;

use App\Filament\Clusters\Cashier;
use App\Filament\Resources\CelebrationResource;
use App\Models\Celebration;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;

class CelebrationChildrenPage extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Celebration Children';

    protected static ?string $title = 'Manage Celebration Children';

    protected static ?string $slug = 'celebration-children';

    protected static ?string $cluster = Cashier::class;

    protected static string $view = 'filament.clusters.cashier.pages.celebration-children';

    #[Url]
    public ?string $celebrationId = null;

    public ?Celebration $celebration = null;

    public $selectedCelebration = null;

    public function mount(): void
    {
        if (empty($this->celebrationId)) {
            $this->celebration = Celebration::latest()->first();
            $this->celebrationId = $this->celebration?->id;
        } else {
            $this->celebration = Celebration::find($this->celebrationId);
        }

        $this->form->fill([
            'selectedCelebration' => $this->celebrationId,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedCelebration')
                    ->label('Select Celebration')
                    ->options(Celebration::orderBy('celebration_date', 'desc')
                        ->get()
                        ->mapWithKeys(function ($celebration) {
                            $childName = $celebration->child->first_name ?? 'Unknown';
                            $date = $celebration->celebration_date?->format('d M Y') ?? 'No date';
                            return [$celebration->id => "{$childName}'s celebration - {$date}"];
                        }))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->celebrationId = $state;
                        $this->celebration = Celebration::find($state);
                    })
                    ->required(),
            ]);
    }

    public function celebrationInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->celebration)
            ->schema([
                TextEntry::make('child.first_name')
                    ->label('Child'),
                TextEntry::make('theme.name')
                    ->label('Theme'),
                TextEntry::make('celebration_date')
                    ->dateTime()
                    ->label('Date'),
                TextEntry::make('children_count')
                    ->numeric()
                    ->label('Children Count'),
                TextEntry::make('parents_count')
                    ->numeric()
                    ->label('Parents Count'),
            ])
            ->columns(5);
    }

    public function goToManageChildren(): void
    {
        if ($this->celebration) {
            $url = CelebrationResource::getUrl('cashier-manage-children', ['record' => $this->celebration]);
            $this->redirect($url);
        }
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->celebration) {
            $childName = $this->celebration->child->first_name ?? 'Unknown';
            return "$childName's Celebration Children";
        }

        return parent::getTitle();
    }
}
