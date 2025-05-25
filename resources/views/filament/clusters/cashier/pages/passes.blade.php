<x-filament-panels::page>
    @if (session()->has('success'))
        <div style="margin-bottom: 1rem; background: #cfc; padding: 1rem;">
            {{ session('success') }}
        </div>
    @endif

    @include('filament.clusters.cashier.components.selected-user-info', ['selectedUser' => $this->selectedUser])

    <div class="mt-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            @if ($this->selectedUser)
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Passes for {{ $this->selectedUser->profile->first_name }} {{ $this->selectedUser->profile->last_name }}</h2>
                    
                    @if($this->selectedUser->family && $this->selectedUser->family->children && $this->selectedUser->family->children->count() > 0)
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Quick Filter:</span>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    type="button" 
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                    wire:click="filterByTicketStatus(['active'])"
                                >
                                    Active Tickets
                                </button>
                                <button 
                                    type="button" 
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-warning-600 hover:bg-warning-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warning-500"
                                    wire:click="filterByTicketStatus(['future'])"
                                >
                                    Future Tickets
                                </button>
                                <button 
                                    type="button" 
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-danger-600 hover:bg-danger-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500"
                                    wire:click="filterByTicketStatus(['expired'])"
                                >
                                    Expired Tickets
                                </button>
                                <button 
                                    type="button" 
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-gray-800 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400"
                                    wire:click="filterByTicketStatus(['active', 'future', 'expired'])"
                                >
                                    All Tickets
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                
                @if($this->selectedUser->family && $this->selectedUser->family->children && $this->selectedUser->family->children->count() > 1)
                    <div class="mb-4 flex flex-wrap gap-2 border-b border-gray-200 pb-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Filter by child:</span>
                        @foreach($this->selectedUser->family->children ?? [] as $child)
                            <button 
                                type="button" 
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                wire:click="filterByChild('{{ $child->id }}')"
                            >
                                {{ $child->first_name }} {{ $child->last_name }}
                            </button>
                        @endforeach
                        <button 
                            type="button" 
                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                            wire:click="filterByChild()"
                        >
                            All Children
                        </button>
                    </div>
                @endif
                
                <div id="cashier-passes-table">
                    @if(method_exists($this, 'renderCustomTable'))
                        {!! $this->renderCustomTable() !!}
                    @elseif(method_exists($this, 'getTableContent'))
                        {{ $this->getTableContent() }}
                    @else
                        <div class="p-4 bg-yellow-50 text-yellow-700 rounded-md">
                            <p>Table functionality is not available. Please try refreshing the page.</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">No Client Selected</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Please select a client to view their passes.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

<!-- JavaScript for handling pass extension -->
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('extend-pass', event => {
            const data = event.detail;
            const minutes = prompt('Enter minutes to extend:', '30');
            if (minutes !== null && minutes.trim() !== '') {
                const minutesNum = parseInt(minutes.trim(), 10);
                if (!isNaN(minutesNum) && minutesNum > 0) {
                    // Call the extendPass method with the entered minutes
                    @this.extendPass(data.serial, minutesNum);
                } else {
                    alert('Please enter a valid number of minutes.');
                }
            }
        });
    });
</script>
@endpush 