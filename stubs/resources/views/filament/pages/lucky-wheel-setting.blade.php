<x-filament-panels::page>
    @include('filament.pages.lucky-wheel-tabs', ['active' => 'settings'])

    {{ $this->content }}
</x-filament-panels::page>
