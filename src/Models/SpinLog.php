<?php

namespace NexusPlugin\LuckyWheel\Models;

use App\Models\NexusModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpinLog extends NexusModel
{
    protected $table = 'plugin_lucky_wheel_logs';

    protected $fillable = [
        'uid',
        'prize_id',
        'cost',
        'reward_type',
        'reward_value',
        'reward_content',
        'prize_name',
        'delivery_status',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'reward_value' => 'decimal:2',
    ];

    public const DELIVERY_STATUS_AUTO = 'auto';
    public const DELIVERY_STATUS_PENDING = 'pending';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid');
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class, 'prize_id');
    }
}
