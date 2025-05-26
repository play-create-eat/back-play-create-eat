<x-filament-panels::page>
    @if ($this->order)
        <div class="flex items-center gap-4">
            <div class="flex-grow">
                <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl mb-2">
                    Tickets Order
                </h1>
                <h3 class="fi-sidebar-item-label flex-1 truncate text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{$this->order}}
                </h3>
            </div>
            <div>
                <x-filament::button color="gray" wire:click="back">
                    Close
                </x-filament::button>
            </div>
        </div>

        @if (empty($this->passes))
            <div class="text-center">
                <h1 class="mt-4 text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl">Order not
                    found</h1>
                <p class="mt-6 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8">Sorry, we couldn't find the
                    order you're looking for.</p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <x-filament::button wire:click="back">Go back</x-filament::button>
                </div>
            </div>
        @else
            @foreach($this->passes as $pass)
                @include('filament.clusters.cashier.components.pass', ['pass' => $pass])
            @endforeach

            <div class="mt-6">
                <x-filament::button wire:click="back" color="gray">
                    Back to Purchase
                </x-filament::button>
            </div>
        @endif
    @else
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
                        Continue to Purchase
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        @endif

    @endif
</x-filament-panels::page>
