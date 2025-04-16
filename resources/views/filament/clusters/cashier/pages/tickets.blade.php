<x-filament-panels::page>

    <x-filament-panels::form>
        {{ $this->form }}
    </x-filament-panels::form>


    @if (session()->has('success'))
        <div style="margin-bottom: 1rem; background: #cfc; padding: 1rem;">
            {{ session('success') }}
        </div>
    @endif

</x-filament-panels::page>
