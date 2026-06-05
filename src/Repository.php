<?php

namespace NexusPlugin\LuckyWheel;

use App\Models\Setting;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Artisan;
use Nexus\Plugin\BasePlugin;
use NexusPlugin\LuckyWheel\Support\MenuManager;

class Repository extends BasePlugin
{
    public const ID = 'lucky-wheel';
    public const VERSION = '1.0.1';
    public const COMPATIBLE_NP_VERSION = '1.10.2';

    public function install(): void
    {
        $this->ensureServiceProviderRegistered();
        $this->publishApplicationFiles();
        $this->patchDefaultMenuFallback();
        $this->runMigrations(__DIR__ . '/../database/migrations');
        $this->seedDefaults();
        MenuManager::syncCustomMenu();
        $this->clearCaches();
    }

    public function uninstall(): void
    {
        $this->runMigrations(__DIR__ . '/../database/migrations', true);
    }

    public function boot(): void
    {
        add_filter('nexus_setting_tabs', [$this, 'addSettingTab']);
        add_filter('nexus_default_menu_items_after_upload', [MenuManager::class, 'renderDefaultMenuItem'], 10, 2);
        add_action('nexus_setting_update', [MenuManager::class, 'syncCustomMenu']);
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function trans($name): string
    {
        return nexus_trans(sprintf('%s.%s', self::ID, $name));
    }

    public function addSettingTab(array $tabs): array
    {
        $tabs[] = Tab::make($this->trans('settings.tab'))
            ->id('lucky-wheel')
            ->schema([
                Radio::make('lucky_wheel.enabled')
                    ->options($this->yesNoOptions())
                    ->inline(true)
                    ->label($this->trans('settings.enabled'))
                    ->default('yes'),
                TextInput::make('lucky_wheel.cost')
                    ->numeric()
                    ->minValue(0)
                    ->default(100)
                    ->label($this->trans('settings.cost')),
                TextInput::make('lucky_wheel.daily_limit')
                    ->numeric()
                    ->minValue(0)
                    ->default(3)
                    ->label($this->trans('settings.daily_limit'))
                    ->helperText($this->trans('settings.daily_limit_help')),
                TextInput::make('lucky_wheel.min_class')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->label($this->trans('settings.min_class'))
                    ->helperText($this->trans('settings.min_class_help')),
                Radio::make('lucky_wheel.show_probability')
                    ->options($this->yesNoOptions())
                    ->inline(true)
                    ->default('no')
                    ->label($this->trans('settings.show_probability'))
                    ->helperText($this->trans('settings.show_probability_help')),
            ])
            ->columns(2);

        return $tabs;
    }

    private function yesNoOptions(): array
    {
        return [
            'yes' => 'yes',
            'no' => 'no',
        ];
    }

    private function seedDefaults(): void
    {
        $defaults = [
            'lucky_wheel.enabled' => 'yes',
            'lucky_wheel.cost' => '100',
            'lucky_wheel.daily_limit' => '3',
            'lucky_wheel.min_class' => '0',
            'lucky_wheel.show_probability' => 'no',
        ];

        foreach ($defaults as $name => $value) {
            Setting::query()->updateOrCreate(
                ['name' => $name],
                ['value' => $value, 'autoload' => 'yes']
            );
        }
    }

    private function publishApplicationFiles(): void
    {
        $stubsPath = dirname(__DIR__) . '/stubs';
        if (!is_dir($stubsPath)) {
            return;
        }

        $files = [
            'app/Filament/Pages/LuckyWheelSettingPage.php',
            'app/Filament/Resources/System/LuckyWheelPrizeResource.php',
            'app/Filament/Resources/System/LuckyWheelPrizeResource/Pages/ManageLuckyWheelPrizes.php',
            'app/Filament/Resources/System/LuckyWheelLogResource.php',
            'app/Filament/Resources/System/LuckyWheelLogResource/Pages/ManageLuckyWheelLogs.php',
            'resources/views/filament/pages/lucky-wheel-tabs.blade.php',
            'resources/views/filament/pages/lucky-wheel-setting.blade.php',
            'resources/views/filament/pages/lucky-wheel-prizes.blade.php',
            'resources/views/filament/pages/lucky-wheel-logs.blade.php',
            'public/luckywheel.php',
            'public/takeluckywheel.php',
        ];

        foreach ($files as $file) {
            $source = $stubsPath . '/' . $file;
            $target = base_path($file);
            if (!is_file($source)) {
                continue;
            }

            if (is_file($target) && sha1_file($target) !== sha1_file($source)) {
                copy($target, $target . '.lucky-wheel.bak');
            }

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            copy($source, $target);
        }

        $this->publishLanguageFiles();
    }

    private function publishLanguageFiles(): void
    {
        $sourceRoot = dirname(__DIR__) . '/resources/lang';
        if (!is_dir($sourceRoot)) {
            return;
        }

        foreach (['en', 'zh_CN', 'zh_TW'] as $locale) {
            $source = $sourceRoot . '/' . $locale . '/messages.php';
            $target = resource_path("lang/{$locale}/lucky-wheel.php");

            if (!is_file($source)) {
                continue;
            }

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            copy($source, $target);
        }
    }

    private function ensureServiceProviderRegistered(): void
    {
        $configPath = config_path('app.php');
        $provider = 'NexusPlugin\\LuckyWheel\\ServiceProvider::class';

        if (!is_file($configPath)) {
            return;
        }

        $content = file_get_contents($configPath);
        if ($content === false || str_contains($content, $provider)) {
            return;
        }

        $needle = '        /*' . PHP_EOL . '         * Package Service Providers...';
        $replacement = "        {$provider}," . PHP_EOL . PHP_EOL . $needle;

        $updated = str_replace($needle, $replacement, $content);
        if ($updated !== $content) {
            file_put_contents($configPath, $updated);
        }
    }

    private function patchDefaultMenuFallback(): void
    {
        $file = base_path('include/functions.php');
        if (!is_file($file)) {
            return;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return;
        }

        $old = "print apply_filter('nexus_default_menu_items_after_upload', '', \$selected);";
        $new = "print apply_filter('nexus_default_menu_items_after_upload', \\NexusPlugin\\LuckyWheel\\Support\\MenuManager::renderDefaultMenuItem('', \$selected), \$selected);";

        if (str_contains($content, $new)) {
            return;
        }

        copy($file, $file . '.lucky-wheel-menu.bak');
        if (str_contains($content, $old)) {
            file_put_contents($file, str_replace($old, $new, $content));

            return;
        }

        $uploadLine = '        print ("<li" . ($selected == "upload" ? " class=\"selected\"" : "") . "><a href=\"upload.php\">".$lang_functions[\'text_upload\']."</a></li>");';
        if (str_contains($content, $uploadLine)) {
            file_put_contents($file, str_replace($uploadLine, $uploadLine . PHP_EOL . '        ' . $new, $content));
        }
    }

    private function clearCaches(): void
    {
        try {
            Artisan::call('optimize:clear');
        } catch (\Throwable) {
            // Cache clearing is a convenience; installation should not fail only because it is unavailable.
        }
    }
}
