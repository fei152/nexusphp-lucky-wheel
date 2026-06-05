<x-filament-panels::page>
    @include('filament.pages.lucky-wheel-tabs', ['active' => 'prizes'])

    {{ $this->table }}
</x-filament-panels::page>
