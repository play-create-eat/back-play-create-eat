<x-filament-panels::page>
    @if (session()->has('success'))
        <div style="margin-bottom: 1rem; background: #cfc; padding: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    @include('filament.clusters.cashier.components.selected-user-info', ['selectedUser' => $this->selectedUser])

    <x-filament-panels::form>
        {{ $this->form }}
        
        @if($this->selectedUserId)
            <div class="mt-4">
                <x-filament::button wire:click="topUp">
                    Top Up Wallet
                </x-filament::button>
            </div>
        @endif
    </x-filament-panels::form>
</x-filament-panels::page> 