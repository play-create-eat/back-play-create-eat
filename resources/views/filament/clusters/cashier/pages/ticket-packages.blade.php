<x-filament-panels::page>
    @if (session()->has('success'))
        <div style="margin-bottom: 1rem; background: #cfc; padding: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    @if(!$this->selectedUser)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Search for a client</h3>
            <x-filament-panels::form wire:submit="search">
                {{ $this->form }}
            </x-filament-panels::form>
        </div>
    @else
        @include('filament.clusters.cashier.components.selected-user-info', ['selectedUser' => $this->selectedUser])
        <x-filament-panels::form wire:submit="submit">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Purchase
                </x-filament::button>
            </div>
        </x-filament-panels::form>
    @endif
</x-filament-panels::page>
