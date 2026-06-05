<?php

namespace NexusPlugin\LuckyWheel\Models;

use App\Models\NexusModel;
use Illuminate\Database\Eloquent\Builder;

class Prize extends NexusModel
{
    protected $table = 'plugin_lucky_wheel_prizes';

    protected $fillable = [
        'name',
        'description',
        'reward_type',
        'reward_value',
        'reward_content',
        'probability',
        'stock',
        'sort',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public const REWARD_TYPE_BONUS = 'bonus';
    public const REWARD_TYPE_UPLOAD = 'upload';
    public const REWARD_TYPE_INVITE = 'invite';
    public const REWARD_TYPE_NO_AD = 'no_ad';
    public const REWARD_TYPE_RAINBOW_ID = 'rainbow_id';
    public const REWARD_TYPE_MEDAL = 'medal';
    public const REWARD_TYPE_VIP = 'vip';
    public const REWARD_TYPE_TEXT = 'text';

    protected static function booted(): void
    {
        static::saving(function (Prize $prize) {
            $prize->description = (string) ($prize->description ?? '');
            $prize->reward_value = $prize->blankToNumber($prize->reward_value);
            $prize->probability = $prize->blankToNumber($prize->probability);
            $prize->sort = (int) $prize->blankToNumber($prize->sort);
            $prize->stock = $prize->stock === '' ? null : $prize->stock;
            $prize->enabled = (bool) $prize->enabled;
        });
    }

    private function blankToNumber(mixed $value): string
    {
        return ($value === '' || $value === null) ? '0' : (string) $value;
    }

    public function setRewardValueAttribute(mixed $value): void
    {
        $this->attributes['reward_value'] = $this->blankToNumber($value);
    }

    public function setProbabilityAttribute(mixed $value): void
    {
        $this->attributes['probability'] = $this->blankToNumber($value);
    }

    public static function rewardTypeOptions(): array
    {
        return [
            self::REWARD_TYPE_BONUS => nexus_trans('lucky-wheel.prize.reward_types.bonus'),
            self::REWARD_TYPE_UPLOAD => nexus_trans('lucky-wheel.prize.reward_types.upload'),
            self::REWARD_TYPE_INVITE => nexus_trans('lucky-wheel.prize.reward_types.invite'),
            self::REWARD_TYPE_NO_AD => nexus_trans('lucky-wheel.prize.reward_types.no_ad'),
            self::REWARD_TYPE_RAINBOW_ID => nexus_trans('lucky-wheel.prize.reward_types.rainbow_id'),
            self::REWARD_TYPE_MEDAL => nexus_trans('lucky-wheel.prize.reward_types.medal'),
            self::REWARD_TYPE_VIP => nexus_trans('lucky-wheel.prize.reward_types.vip'),
            self::REWARD_TYPE_TEXT => nexus_trans('lucky-wheel.prize.reward_types.text'),
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->enabled()->where(function (Builder $query) {
            $query->whereNull('stock')->orWhere('stock', '>', 0);
        });
    }

    public function getStockTextAttribute(): string
    {
        return is_null($this->stock) ? nexus_trans('lucky-wheel.prize.unlimited') : (string) $this->stock;
    }
}

