@if($selectedUser)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mt-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Selected Client</h3>
            <button 
                type="button"
                wire:click="clearSelectedUser"
                class="inline-flex items-center justify-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <span class="sr-only">Clear</span>
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>
        
        <div class="flex items-center space-x-4">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ $selectedUser->profile->first_name }} {{ $selectedUser->profile->last_name }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ $selectedUser->email }}
                </p>
                @if($selectedUser->profile->phone_number)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $selectedUser->profile->phone_number }}
                    </p>
                @endif
            </div>
        </div>
        
        @if($selectedUser->family)
            <div class="mt-4">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Family</h4>
                <div class="mt-2">
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $selectedUser->family->name }}
                    </p>
                    
                    @if($selectedUser->family->main_wallet)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            <span class="font-medium">Balance:</span> 
                            {{ number_format($selectedUser->family->main_wallet->balance / 100, 2) }} €
                        </p>
                    @endif
                    
                    @if($selectedUser->family->loyalty_wallet)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Loyalty Points:</span> 
                            {{ $selectedUser->family->loyalty_wallet->balance }}
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif 