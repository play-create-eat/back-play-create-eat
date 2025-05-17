<x-filament-panels::page>
    @if($transaction && count($receipt) > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Payment Receipt</h2>
                    <button
                        type="button"
                        onclick="window.print();"
                        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium
                        bg-primary-600 hover:bg-primary-700 text-white">
                        <x-heroicon-o-printer class="w-5 h-5 mr-2" />
                        Print
                    </button>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $receipt['transaction_id'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $receipt['date'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Client</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $receipt['client_name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Child</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $receipt['child_name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount Paid</dt>
                            <dd class="mt-1 text-sm font-medium text-green-600 dark:text-green-400">
                                {{ number_format($receipt['amount'], 2) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Remaining Balance</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ number_format($receipt['remaining'], 2) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cashier</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $receipt['cashier'] }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-8">
                    <button
                        type="button"
                        wire:click="back"
                        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium
                        bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Back to Payment
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <form wire:submit="submit">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button
                        type="submit"
                        :disabled="blank($this->data['celebration_id'])"
                    >
                        Process Payment
                    </x-filament::button>
                </div>
            </form>
        </div>
    @endif
</x-filament-panels::page>
