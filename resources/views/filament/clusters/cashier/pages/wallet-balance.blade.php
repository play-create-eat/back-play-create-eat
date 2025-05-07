<x-filament::page>
    <div class="space-y-6">
        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
            {{ $this->form }}
        </div>

        @if ($this->selectedUser && $this->selectedUser->family)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Wallet balances -->
                <div class="lg:col-span-2 p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                    <div class="flex items-center mb-4">
                        <x-heroicon-o-wallet class="w-6 h-6 text-primary-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Wallet Balances</h3>
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

                    <div class="mt-6">
                        <x-filament::button wire:click="submit"
                            class="w-full flex justify-center flex-row md:w-auto bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300">
                            Top Up Wallet
                        </x-filament::button>
                    </div>
                </div>

                <!-- User info card -->
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                    <div class="flex items-center mb-4">
                        <x-heroicon-o-user-circle class="w-6 h-6 text-primary-500 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Client Info</h3>
                    </div>

                    <div class="space-y-4">
                        @if ($this->selectedUser->profile)
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-16 h-16 flex-shrink-0 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                    <div
                                        class="flex items-center justify-center w-full h-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-xl font-semibold">
                                        {{ substr($this->selectedUser->profile->first_name ?? '', 0, 1) }}{{ substr($this->selectedUser->profile->last_name ?? '', 0, 1) }}
                                    </div>
                                </div>
                                <div class="ml-4 gap-4">
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mr-4">
                                        {{ $this->selectedUser->profile->first_name }}
                                        {{ $this->selectedUser->profile->last_name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $this->selectedUser->email }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Family</h5>
                            <p class="text-gray-900 dark:text-white font-medium">
                                {{ $this->selectedUser->family->name }}
                            </p>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Children</h5>
                            <div class="space-y-2">
                                @forelse($this->selectedUser->family->children as $child)
                                    <div class="flex items-center">
                                        <div
                                            class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-800 dark:text-indigo-200">
                                            {{ substr($child->first_name, 0, 1) }}
                                        </div>
                                        <span class="ml-2 text-gray-800 dark:text-gray-200">
                                            {{ $child->first_name }} {{ $child->last_name }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No children registered</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            @if (count($recentTransactions) > 0)
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <x-heroicon-o-receipt-refund class="w-6 h-6 text-primary-500 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Wallet
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($recentTransactions as $transaction)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ $transaction['date'] }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <div class="flex items-center">
                                                @if ($transaction['wallet'] === 'Main Wallet')
                                                    <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                                                @else
                                                    <div class="w-2 h-2 bg-blue-400 rounded-full mr-2"></div>
                                                @endif
                                                {{ $transaction['wallet'] }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            {{ $transaction['description'] ?? 'Transaction' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($transaction['type'] === 'deposit')
                                                <span
                                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Deposit
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Withdraw
                                                </span>
                                            @endif
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $transaction['type'] === 'deposit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $transaction['type'] === 'deposit' ? '+' : '-' }}{{ $transaction['amount'] }}
                                            €
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament::page>
