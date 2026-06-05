<?php

namespace App\Filament\Resources\System\LuckyWheelPrizeResource\Pages;

use App\Filament\PageListSingle;
use App\Filament\Pages\LuckyWheelSettingPage;
use App\Filament\Resources\System\LuckyWheelLogResource;
use App\Filament\Resources\System\LuckyWheelPrizeResource;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ManageLuckyWheelPrizes extends PageListSingle
{
    protected static string $resource = LuckyWheelPrizeResource::class;

    public function getTitle(): string
    {
        return nexus_trans('lucky-wheel.admin.nav');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.pages.lucky-wheel-tabs')
                    ->viewData(['active' => 'prizes']),
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }
}

