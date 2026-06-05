<?php

namespace NexusPlugin\LuckyWheel\Support;

use App\Models\Setting;
use Nexus\Database\NexusDB;
use Nexus\Plugin\Plugin;
use NexusPlugin\LuckyWheel\Repository;

class MenuManager
{
    public const URL = 'luckywheel.php';
    public const SORT = 750;

    public static function isEnabled(): bool
    {
        return self::settingValue('lucky_wheel.enabled', 'yes') === 'yes';
    }

    public static function syncCustomMenu(): void
    {
        if (!self::customMenuAvailable()) {
            return;
        }

        $item = self::customMenuItem();

        if (!self::isEnabled()) {
            self::setCustomMenuEnabled(false);

            return;
        }

        if ($item === null) {
            $item = self::newCustomMenuItem();
            $item->fill([
                'parent_id' => 0,
                'text' => self::translations(),
                'target' => '_self',
                'style' => '',
                'sort' => self::SORT,
                'min_class' => (int) self::settingValue('lucky_wheel.min_class', 0),
                'enabled' => true,
            ]);
            $item->save();

            return;
        }

        self::setCustomMenuEnabled(true);
    }

    public static function renderDefaultMenuItem(string $html = '', string $selected = ''): string
    {
        if ($html !== '' || self::customMenuWillRender() || !self::isEnabled()) {
            return $html;
        }

        $label = e(self::label());
        $class = self::isLuckyWheelSelected($selected) ? ' class="selected"' : '';

        return sprintf('<li%s><a href="%s">%s</a></li>', $class, self::URL, $label);
    }

    private static function customMenuWillRender(): bool
    {
        return Plugin::getById('custom-menu') !== null
            && self::customMenuAvailable()
            && self::settingValue('menu.enable', 'yes') === 'yes';
    }

    private static function customMenuAvailable(): bool
    {
        return class_exists(self::customMenuModel()) && self::tableExists('plugin_custom_menu_items');
    }

    private static function customMenuModel(): string
    {
        return '\\NexusPlugin\\CustomMenu\\Models\\MenuItem';
    }

    private static function customMenuItem(): mixed
    {
        $model = self::customMenuModel();

        return $model::query()->where('url', self::URL)->first();
    }

    private static function newCustomMenuItem(): mixed
    {
        $model = self::customMenuModel();

        return new $model(['url' => self::URL]);
    }

    private static function isLuckyWheelSelected(string $selected): bool
    {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return $selected === Repository::ID || $script === self::URL;
    }

    private static function label(): string
    {
        $lang = function_exists('get_langfolder_cookie') ? get_langfolder_cookie() : 'en';

        return self::translations()[$lang] ?? self::translations()['en'];
    }

    private static function translations(): array
    {
        return [
            'en' => 'Lucky Wheel',
            'chs' => '幸运大转盘',
            'cht' => '幸運大轉盤',
        ];
    }

    private static function settingValue(string $name, mixed $default = null): mixed
    {
        if (!self::tableExists('settings')) {
            return $default;
        }

        if (!self::isTraditionalRuntime()) {
            return Setting::query()->where('name', $name)->value('value') ?? $default;
        }

        $row = NexusDB::getOne('settings', "name = " . sqlesc($name), 'value');

        return $row['value'] ?? $default;
    }

    private static function setCustomMenuEnabled(bool $enabled): void
    {
        $model = self::customMenuModel();

        if (self::isTraditionalRuntime()) {
            NexusDB::update(
                'plugin_custom_menu_items',
                ['enabled' => $enabled ? 1 : 0],
                "url = " . sqlesc(self::URL)
            );

            return;
        }

        $model::query()->where('url', self::URL)->update(['enabled' => $enabled]);
    }

    private static function isTraditionalRuntime(): bool
    {
        return defined('IN_NEXUS') && IN_NEXUS;
    }

    private static function tableExists(string $table): bool
    {
        try {
            return NexusDB::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
