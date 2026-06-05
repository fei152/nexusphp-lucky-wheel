<x-filament-panels::page>
    @include('filament.pages.lucky-wheel-tabs', ['active' => 'logs'])

    {{ $this->table }}
</x-filament-panels::page>
