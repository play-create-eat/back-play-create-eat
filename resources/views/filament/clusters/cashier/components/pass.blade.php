@php
    use Carbon\CarbonInterval;
    use Filament\Infolists\Components\TextEntry;
    use Filament\Infolists\Components\IconEntry;

    $schema = [
        TextEntry::make('activation_date')->date(),
        TextEntry::make('remaining_time')
            ->formatStateUsing(fn (int $state): string => CarbonInterval::minutes($state)->cascade()->forHumans()),
        IconEntry::make('is_extendable')->boolean(),
        TextEntry::make('created_at')->dateTime(),
        TextEntry::make('expires_at')->dateTime(),
    ];
@endphp
<x-filament::section collapsible collapsed>
    <x-slot name="heading">
        <div class="flex items-center gap-2 mb-2">
            @if ($pass->is_expired)
                <x-filament::badge color="warning" size="sm">
                    Expired
                </x-filament::badge>
            @else
                <x-filament::badge color="success" size="sm">
                    Active
                </x-filament::badge>
            @endif
            <p>{{$pass->children->first_name}} {{$pass->children->last_name}}</p>
        </div>
    </x-slot>

    <x-slot name="description">
        <span class="font-mono">{{$pass->serial}}</span>
    </x-slot>

    @unless($pass->is_expired)
        <x-slot name="headerEnd">
            <x-filament::button wire:click="openNewUserModal" icon="heroicon-o-qr-code">
                Print
            </x-filament::button>
        </x-slot>
    @endunless

    {{ \Filament\Infolists\Infolist::make()
        ->schema($schema)
        ->record($pass)
        ->inlineLabel(true) }}

</x-filament::section>
