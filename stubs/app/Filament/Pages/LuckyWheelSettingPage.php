<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use NexusPlugin\LuckyWheel\Support\MenuManager;

class LuckyWheelSettingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.lucky-wheel-setting';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return nexus_trans('lucky-wheel.admin.nav');
    }

    public function getTitle(): string
    {
        return nexus_trans('lucky-wheel.admin.nav');
    }

    public function mount(): void
    {
        $this->content->fill([
            'enabled' => Setting::get('lucky_wheel.enabled', 'yes'),
            'cost' => Setting::get('lucky_wheel.cost', 100),
            'daily_limit' => Setting::get('lucky_wheel.daily_limit', 3),
            'min_class' => Setting::get('lucky_wheel.min_class', User::CLASS_PEASANT),
            'show_probability' => Setting::get('lucky_wheel.show_probability', 'no'),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(nexus_trans('lucky-wheel.admin.settings'))
                    ->schema([
                        Radio::make('enabled')
                            ->label(nexus_trans('lucky-wheel.settings.enabled'))
                            ->options([
                                'yes' => 'yes',
                                'no' => 'no',
                            ])
                            ->inline()
                            ->required(),
                        TextInput::make('cost')
                            ->label(nexus_trans('lucky-wheel.settings.cost'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('daily_limit')
                            ->label(nexus_trans('lucky-wheel.settings.daily_limit'))
                            ->helperText(nexus_trans('lucky-wheel.settings.daily_limit_help'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Select::make('min_class')
                            ->label(nexus_trans('lucky-wheel.settings.min_class'))
                            ->helperText(nexus_trans('lucky-wheel.settings.min_class_help'))
                            ->options(User::listClass())
                            ->required(),
                        Radio::make('show_probability')
                            ->label(nexus_trans('lucky-wheel.settings.show_probability'))
                            ->helperText(nexus_trans('lucky-wheel.settings.show_probability_help'))
                            ->options([
                                'yes' => 'yes',
                                'no' => 'no',
                            ])
                            ->inline()
                            ->required(),
                    ])
                    ->columns(2),
                Action::make('submit')
                    ->label(__('label.save'))
                    ->action(fn () => $this->submit()),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $state = $this->content->getState();
        $rows = [
            ['name' => 'lucky_wheel.enabled', 'value' => $state['enabled'], 'autoload' => 'yes'],
            ['name' => 'lucky_wheel.cost', 'value' => (string) $state['cost'], 'autoload' => 'yes'],
            ['name' => 'lucky_wheel.daily_limit', 'value' => (string) $state['daily_limit'], 'autoload' => 'yes'],
            ['name' => 'lucky_wheel.min_class', 'value' => (string) $state['min_class'], 'autoload' => 'yes'],
            ['name' => 'lucky_wheel.show_probability', 'value' => $state['show_probability'], 'autoload' => 'yes'],
        ];

        Setting::query()->upsert($rows, ['name'], ['value', 'autoload']);
        clear_setting_cache();
        MenuManager::syncCustomMenu();
        send_admin_success_notification();
    }
}

