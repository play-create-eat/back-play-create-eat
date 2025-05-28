<?php

namespace App\Filament\Clusters\Cashier\Resources\PassResource\Pages;

use App\Filament\Clusters\Cashier\Concerns\HasGlobalUserSearch;
use App\Filament\Clusters\Cashier\Concerns\HasUserSearchForm;
use App\Filament\Clusters\Cashier\Resources\PassResource;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Log;

class ListPasses extends ListRecords implements HasForms
{
    use HasGlobalUserSearch;
    use InteractsWithForms;
    use HasUserSearchForm;

    protected static string $resource = PassResource::class;

    protected static string $view = 'filament.clusters.cashier.resources.pass-resource.pages.list-passes';

    public ?array $data = [];

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        parent::mount();

        if (method_exists($this, 'bootHasGlobalUserSearch')) {
            $this->bootHasGlobalUserSearch();
        }

        $this->mountHasUserSearchForm();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $schema = [];
        if (!$this->selectedUser) {
            $schema[]  = $this->getUserSearchComponent();
        }
        return $form
            ->schema($schema)
            ->statePath('data');
    }


    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'available' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->available()),
            'expired' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->expired()),
            'playground' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->active()),
        ];
    }

    public function getListeners(): array
    {
        return [
            'user-selected' => 'refreshForm',
        ];
    }

    public function refreshForm(): void
    {
        Log::info('Refreshing passes form', [
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

    public function clearSelectedUser(): void
    {
        $this->selectUser(null);
        $this->refreshUserSearchForm();
        $this->form->fill();
        $this->resetTable();
    }
}
