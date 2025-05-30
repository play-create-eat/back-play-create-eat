<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Widgets (Stats and Charts) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->getHeaderWidgets() as $widget)
                @livewire(\Livewire\Livewire::getAlias($widget))
            @endforeach
        </div>

        <!-- Main Table -->
        <div class="bg-white dark:bg-gray-900 shadow rounded-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    All Wallet Transactions
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Complete overview of all wallet deposits and withdrawals with payment method breakdown
                </p>
            </div>

            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
