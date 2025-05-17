<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    @if($celebration)
        <x-filament::section>
            {{ $this->celebrationInfolist }}
        </x-filament::section>

        <x-filament::section>
            <div class="flex justify-center mt-4">
                <x-filament::button
                    size="lg"
                    icon="heroicon-o-user-group"
                    wire:click="goToManageChildren"
                    color="primary">
                    Manage Children for This Celebration
                </x-filament::button>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="flex items-center justify-center p-4">
                <x-filament::icon
                    icon="heroicon-o-exclamation-circle"
                    class="h-10 w-10 text-warning-500"
                />

                <p class="ml-2 text-lg font-medium">
                    No celebrations found. Please create a celebration first.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
