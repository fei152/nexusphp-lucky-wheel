<?php

namespace App\Filament\Resources\System;

use App\Filament\Resources\System\LuckyWheelLogResource\Pages\ManageLuckyWheelLogs;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use NexusPlugin\LuckyWheel\Models\SpinLog;

class LuckyWheelLogResource extends Resource
{
    protected static ?string $model = SpinLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 13;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return nexus_trans('lucky-wheel.admin.logs');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('username_for_admin')
                    ->label(__('label.username')),
                TextColumn::make('prize_name')
                    ->label(nexus_trans('lucky-wheel.fields.name')),
                TextColumn::make('cost')
                    ->label(nexus_trans('lucky-wheel.fields.cost')),
                TextColumn::make('reward_type')
                    ->label(nexus_trans('lucky-wheel.fields.reward_type')),
                TextColumn::make('reward_value')
                    ->label(nexus_trans('lucky-wheel.fields.reward_value')),
                TextColumn::make('delivery_status')
                    ->label(nexus_trans('lucky-wheel.fields.delivery_status')),
                TextColumn::make('created_at')
                    ->label(__('label.created_at')),
            ])
            ->filters([
                SelectFilter::make('delivery_status')
                    ->options([
                        'auto' => 'auto',
                        'pending' => 'pending',
                    ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLuckyWheelLogs::route('/'),
        ];
    }
}

