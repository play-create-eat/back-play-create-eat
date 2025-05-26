<?php

namespace App\Filament\Clusters\Cashier\Concerns;

use Filament\Forms\Components\Component;
use Filament\Forms\Form;

trait HasUserSearchForm
{
    public ?array $searchData = [];

    protected function getUserSearchComponent(): Component
    {
        return $this->getUserSearchField()
            ->visible(fn() => !$this->selectedUser)
            ->columnSpanFull();
    }

    public function mountHasUserSearchForm(): void
    {
        $this->searchData = [];
    }

    public function refreshUserSearchForm(): void
    {
        $this->searchData = [];
    }

}
