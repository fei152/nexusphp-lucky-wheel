@php
    $tabs = [
        'settings' => ['label' => nexus_trans('lucky-wheel.admin.settings_tab'), 'url' => \App\Filament\Pages\LuckyWheelSettingPage::getUrl()],
        'prizes' => ['label' => nexus_trans('lucky-wheel.admin.prizes_tab'), 'url' => \App\Filament\Resources\System\LuckyWheelPrizeResource::getUrl()],
        'logs' => ['label' => nexus_trans('lucky-wheel.admin.logs_tab'), 'url' => \App\Filament\Resources\System\LuckyWheelLogResource::getUrl()],
    ];
@endphp

<x-filament::tabs contained>
    @foreach ($tabs as $tabKey => $tab)
        <x-filament::tabs.item
            tag="a"
            :href="$tab['url']"
            :active="($active ?? 'settings') === $tabKey"
        >
            {{ $tab['label'] }}
        </x-filament::tabs.item>
    @endforeach
</x-filament::tabs>

