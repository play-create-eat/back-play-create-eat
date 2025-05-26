<x-filament-panels::page>
    @if(!$this->selectedUser)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Search for a client</h3>
            <x-filament-panels::form wire:submit="search">
                {{ $this->form }}
            </x-filament-panels::form>
        </div>
    @else
        @include('filament.clusters.cashier.components.selected-user-info', ['selectedUser' => $this->selectedUser])

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Select a Celebration</h3>
            <x-filament-panels::form>
                {{ $this->form }}
            </x-filament-panels::form>
        </div>

        @if($this->celebration)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Celebration Details</h3>
                {{ $this->celebrationInfolist }}
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add Guest Child to Celebration</h3>
                    @if($this->guestUser)
                        <button type="button" wire:click="clearGuestUser" class="text-sm text-gray-500 hover:text-gray-700">
                            Clear Guest User
                        </button>
                    @endif
                </div>

                @if(!$this->guestUser)
                    <x-filament-panels::form wire:submit="searchGuest">
                        {{ $this->guestSearchForm }}

                        <div class="mt-4">
                            <x-filament::button type="submit">
                                Select User
                            </x-filament::button>
                        </div>
                    </x-filament-panels::form>
                @else
                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 text-blue-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $this->guestUser->profile->first_name ?? '' }} {{ $this->guestUser->profile->last_name ?? '' }}
                                </h3>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <div>{{ $this->guestUser->email }}</div>
                                    @if($this->guestUser->profile?->phone_number)
                                        <div>{{ $this->guestUser->profile->phone_number }}</div>
                                    @endif
                                    @if($this->guestUser->family?->name)
                                        <div>Family: {{ $this->guestUser->family->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-filament-panels::form wire:submit="addChild">
                        {{ $this->addChildForm }}

                        <div class="mt-4">
                            <x-filament::button
                                type="submit"
                            >
                                Add Child
                            </x-filament::button>
                        </div>
                    </x-filament-panels::form>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Invited Children</h3>
                </div>

                {{ $this->table }}
            </div>
        @endif
    @endif
</x-filament-panels::page>
