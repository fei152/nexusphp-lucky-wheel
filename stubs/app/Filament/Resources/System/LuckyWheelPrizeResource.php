<?php

namespace App\Filament\Resources\System;

use App\Filament\Resources\System\LuckyWheelPrizeResource\Pages\ManageLuckyWheelPrizes;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use NexusPlugin\LuckyWheel\Models\Prize;

class LuckyWheelPrizeResource extends Resource
{
    protected static ?string $model = Prize::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift-top';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 12;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return nexus_trans('lucky-wheel.admin.prizes');
    }

    public static function getBreadcrumb(): string
    {
        return self::getNavigationLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(nexus_trans('lucky-wheel.admin.prize_form'))
                ->schema([
                    TextInput::make('name')
                        ->label(nexus_trans('lucky-wheel.fields.name'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('description')
                        ->label(nexus_trans('lucky-wheel.fields.description'))
                        ->required(false)
                        ->default('')
                        ->dehydrateStateUsing(fn ($state) => $state ?? '')
                        ->maxLength(255),
                    Select::make('reward_type')
                        ->label(nexus_trans('lucky-wheel.fields.reward_type'))
                        ->options(Prize::rewardTypeOptions())
                        ->required()
                        ->default(Prize::REWARD_TYPE_BONUS),
                    TextInput::make('reward_value')
                        ->label(nexus_trans('lucky-wheel.fields.reward_value'))
                        ->numeric()
                        ->default(0)
                        ->dehydrateStateUsing(fn ($state) => $state === '' || $state === null ? 0 : $state)
                        ->helperText(nexus_trans('lucky-wheel.fields.reward_value_help')),
                    Textarea::make('reward_content')
                        ->label(nexus_trans('lucky-wheel.fields.reward_content'))
                        ->required(false)
                        ->helperText(nexus_trans('lucky-wheel.fields.reward_content_help'))
                        ->rows(3),
                    TextInput::make('probability')
                        ->label(nexus_trans('lucky-wheel.fields.probability'))
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('stock')
                        ->label(nexus_trans('lucky-wheel.fields.stock'))
                        ->numeric()
                        ->required(false)
                        ->dehydrateStateUsing(fn ($state) => $state === '' ? null : $state)
                        ->helperText(nexus_trans('lucky-wheel.fields.stock_help')),
                    TextInput::make('sort')
                        ->label(__('label.priority'))
                        ->numeric()
                        ->default(0)
                        ->dehydrateStateUsing(fn ($state) => $state === '' || $state === null ? 0 : $state),
                    Toggle::make('enabled')
                        ->label(__('label.enabled'))
                        ->default(true),
                    Placeholder::make('probability_notice')
                        ->label(nexus_trans('lucky-wheel.fields.probability_notice'))
                        ->content(nexus_trans('lucky-wheel.fields.probability_help')),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('name')
                    ->label(nexus_trans('lucky-wheel.fields.name'))
                    ->searchable(),
                TextColumn::make('reward_type')
                    ->label(nexus_trans('lucky-wheel.fields.reward_type')),
                TextColumn::make('reward_value')
                    ->label(nexus_trans('lucky-wheel.fields.reward_value')),
                TextColumn::make('probability')
                    ->label(nexus_trans('lucky-wheel.fields.probability'))
                    ->sortable(),
                TextColumn::make('stock_text')
                    ->label(nexus_trans('lucky-wheel.fields.stock')),
                TextColumn::make('sort')
                    ->label(__('label.priority'))
                    ->sortable(),
                IconColumn::make('enabled')
                    ->label(__('label.enabled'))
                    ->boolean(),
            ])
            ->defaultSort('sort', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLuckyWheelPrizes::route('/'),
        ];
    }
}

