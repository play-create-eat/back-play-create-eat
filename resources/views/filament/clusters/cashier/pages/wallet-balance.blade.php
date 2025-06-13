<x-filament::page>
    @include('filament.clusters.cashier.components.selected-user-info', ['selectedUser' => $this->selectedUser])
    <div class="space-y-6">
        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center mb-4">
                @if($this->selectedUser)
                    <x-heroicon-o-banknotes class="w-6 h-6 text-primary-500 mr-4" />
                @endif
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white ml-4">
                    @if(!$this->selectedUser)
                        Search for a client
                    @elseif($this->canTopUpWallet())
                        Top Up Wallet
                    @else
                        View Wallet
                    @endif
                </h3>
            </div>

            {{ $this->form }}
        </div>

        @if ($this->selectedUser && $this->selectedUser->family)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                    <div class="flex items-center mb-4">
                        <x-heroicon-o-wallet class="w-6 h-6 text-primary-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Balances</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            class="relative p-4 rounded-lg border border-green-100 bg-gradient-to-br flex flex-row justify-between items-center from-green-50 to-white dark:from-gray-700 dark:to-gray-800 dark:border-gray-700">
                            <div>
                                <h4 class="font-medium text-gray-700 dark:text-gray-200">Main Wallet</h4>
                                <p class="text-2xl font-bold mt-2 text-green-600 dark:text-green-400">
                                    {{ number_format($this->selectedUser->family->main_wallet->balance / 100, 2) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    Available for purchases
                                </p>
                            </div>
                            <div class=" p-3">
                                <x-heroicon-o-wallet class="w-8 h-8 text-green-200 dark:text-green-700" />
                            </div>
                        </div>

                        <div
                            class="relative p-4 rounded-lg border border-blue-100 bg-gradient-to-br flex flex-row justify-between items-center from-blue-50 to-white dark:from-gray-700 dark:to-gray-800 dark:border-gray-700">
                            <div class="flex flex-col gap-2 p-4">
                                <h4 class="font-medium text-gray-700 dark:text-gray-200">Cashback Wallet</h4>
                                <p class="text-2xl font-bold mt-2 text-blue-600 dark:text-blue-400">
                                    {{ number_format($this->selectedUser->family->loyalty_wallet->balance / 100, 2) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    Loyalty rewards
                                </p>
                            </div>
                            <div class=" p-3">
                                <x-heroicon-o-gift class="w-8 h-8 text-blue-200 dark:text-blue-700" />
                            </div>
                        </div>
                    </div>

                    @if($this->canTopUpWallet())
                        <div class="mt-6">
                            <x-filament::button wire:click="submit"
                                class="w-full flex justify-center flex-row md:w-auto bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300">
                                Top Up Wallet
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center mb-4">
                        <x-heroicon-o-receipt-refund class="w-6 h-6 text-primary-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transactions</h3>
                    </div>
                </div>

                {{ $this->table }}
            </div>
        @endif
    </div>
</x-filament::page>
