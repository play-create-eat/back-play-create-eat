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
use Session;
use App\Models\Pass;

class ListPasses extends ListRecords implements HasForms
{
    use HasGlobalUserSearch;
    use InteractsWithForms;
    use HasUserSearchForm;

    protected static string $resource = PassResource::class;

    protected static string $view = 'filament.clusters.cashier.resources.pass-resource.pages.list-passes';

    public ?array $data = [];

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
            $schema[] = $this->getUserSearchComponent();
        }
        return $form
            ->schema($schema)
            ->statePath('data');
    }

    public function getTabs(): array
    {
        $selectedUserId = Session::get('cashier.selected_user_id');
        $selectedUser = null;

        if ($selectedUserId) {
            $selectedUser = User::with(['family.children'])->find($selectedUserId);
        }

        return [
            'all' => Tab::make()
                ->badge(function () use ($selectedUser) {
                    $query = Pass::query();
                    if ($selectedUser?->family?->children->isNotEmpty()) {
                        $childrenIds = $selectedUser->family->children->pluck('id')->toArray();
                        $query->where('user_id', $selectedUser->id)
                              ->whereIn('child_id', $childrenIds);
                    }
                    return $query->count();
                }),

            'available' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->available())
                ->badge(function () use ($selectedUser) {
                    $query = Pass::available();
                    if ($selectedUser?->family?->children->isNotEmpty()) {
                        $childrenIds = $selectedUser->family->children->pluck('id')->toArray();
                        $query->where('user_id', $selectedUser->id)
                              ->whereIn('child_id', $childrenIds);
                    }
                    return $query->count();
                }),

            'expired' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->expired())
                ->badge(function () use ($selectedUser) {
                    $query = Pass::expired();
                    if ($selectedUser?->family?->children->isNotEmpty()) {
                        $childrenIds = $selectedUser->family->children->pluck('id')->toArray();
                        $query->where('user_id', $selectedUser->id)
                              ->whereIn('child_id', $childrenIds);
                    }
                    return $query->count();
                }),

            'playground' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->active())
                ->badge(function () use ($selectedUser) {
                    $query = Pass::active();
                    if ($selectedUser?->family?->children->isNotEmpty()) {
                        $childrenIds = $selectedUser->family->children->pluck('id')->toArray();
                        $query->where('user_id', $selectedUser->id)
                              ->whereIn('child_id', $childrenIds);
                    }
                    return $query->count();
                }),

            'today' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(function () use ($selectedUser) {
                    $query = Pass::whereDate('created_at', today());
                    if ($selectedUser?->family?->children->isNotEmpty()) {
                        $childrenIds = $selectedUser->family->children->pluck('id')->toArray();
                        $query->where('user_id', $selectedUser->id)
                              ->whereIn('child_id', $childrenIds);
                    }
                    return $query->count();
                }),
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

    protected function getHeaderActions(): array
    {
        return [];
    }
}
