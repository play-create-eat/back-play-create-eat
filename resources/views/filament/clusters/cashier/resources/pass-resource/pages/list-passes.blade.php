<x-filament-panels::page>
    @if($selectedUser)
        @include('filament.clusters.cashier.components.selected-user-info', ['selectedUser' => $selectedUser])
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Search for a client</h3>
            {{ $this->form }}
        </div>
    @endif

        <div class="mb-4 flex justify-center">
            <x-filament::tabs>
                @foreach($this->getTabs() as $key => $tab)
                    <x-filament::tabs.item
                        :active="$this->activeTab === $key"
                        wire:click="$set('activeTab', '{{ $key }}')"
                        :badge="$tab->getBadge()"
                    >
                        {{ $tab->getLabel() ?? Str::headline($key) }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>


        {{ $this->table }}
</x-filament-panels::page>
