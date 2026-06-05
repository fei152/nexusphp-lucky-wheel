<?php

namespace NexusPlugin\LuckyWheel\Services;

use App\Models\BonusLogs;
use App\Models\Invite;
use App\Models\Medal;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserMedal;
use App\Models\UserMeta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use NexusPlugin\LuckyWheel\Models\Prize;
use NexusPlugin\LuckyWheel\Models\SpinLog;

class LuckyWheelService
{
    public function getPublicConfig(User $user): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'cost' => $this->getCost(),
            'daily_limit' => $this->getDailyLimit(),
            'remaining_times' => $this->getRemainingTimes($user),
            'min_class' => $this->getMinClass(),
            'show_probability' => $this->shouldShowProbability(),
        ];
    }

    public function listPublicPrizes(): Collection
    {
        return Prize::query()
            ->available()
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get();
    }

    public function listRecentLogs(): Collection
    {
        return SpinLog::query()
            ->with('user')
            ->latest('id')
            ->limit(20)
            ->get();
    }

    public function listUserLogs(User $user): Collection
    {
        return SpinLog::query()
            ->where('uid', $user->id)
            ->latest('id')
            ->limit(20)
            ->get();
    }

    public function spin(User $user): array
    {
        $this->validateSpin($user);

        return $user->getConnection()->transaction(function () use ($user) {
            /** @var User $freshUser */
            $freshUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->validateSpin($freshUser);

            $prizes = Prize::query()
                ->available()
                ->lockForUpdate()
                ->orderByDesc('sort')
                ->orderBy('id')
                ->get();

            if ($prizes->isEmpty()) {
                throw new \RuntimeException(nexus_trans('lucky-wheel.message.no_prize'));
            }

            $prize = $this->pickPrize($prizes);
            $cost = $this->getCost();
            $oldBonus = (float) $freshUser->seedbonus;
            $newBonus = $oldBonus - $cost;

            if ($newBonus < 0) {
                throw new \RuntimeException(nexus_trans('lucky-wheel.message.not_enough_bonus'));
            }

            $freshUser->update(['seedbonus' => $newBonus]);

            $deliveryStatus = SpinLog::DELIVERY_STATUS_PENDING;
            $deliveryResult = $this->deliverPrize($freshUser, $prize, $newBonus);
            $newBonus = $deliveryResult['seedbonus'];
            if ($deliveryResult['delivered']) {
                $deliveryStatus = SpinLog::DELIVERY_STATUS_AUTO;
            }

            if (!is_null($prize->stock)) {
                $prize->decrement('stock');
                $prize->refresh();
            }

            $log = SpinLog::query()->create([
                'uid' => $freshUser->id,
                'prize_id' => $prize->id,
                'cost' => $cost,
                'reward_type' => $prize->reward_type,
                'reward_value' => $prize->reward_value,
                'reward_content' => $deliveryResult['content'] ?: $prize->reward_content,
                'prize_name' => $prize->name,
                'delivery_status' => $deliveryStatus,
            ]);

            if (!$deliveryResult['delivered']) {
                $this->notifyManualPrize($freshUser, $prize, $log);
            }

            BonusLogs::add(
                $freshUser->id,
                $oldBonus,
                $newBonus - $oldBonus,
                $newBonus,
                sprintf('Lucky wheel: %s', $prize->name),
                BonusLogs::BUSINESS_TYPE_LUCKY_DRAW
            );

            return [
                'message' => nexus_trans('lucky-wheel.message.spin_success'),
                'prize' => [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'description' => $prize->description,
                    'reward_type' => $prize->reward_type,
                    'reward_value' => $prize->reward_value,
                    'reward_content' => $deliveryResult['content'] ?: $prize->reward_content,
                    'delivery_status' => $deliveryStatus,
                ],
                'remaining_times' => $this->getRemainingTimes($freshUser),
                'seedbonus' => $newBonus,
                'log_id' => $log->id,
                'log' => [
                    'id' => $log->id,
                    'uid' => $freshUser->id,
                    'username' => $freshUser->username,
                    'prize_name' => $log->prize_name,
                    'delivery_status' => $log->delivery_status,
                    'created_at' => (string) $log->created_at,
                ],
            ];
        });
    }

    public function isEnabled(): bool
    {
        return Setting::get('lucky_wheel.enabled', 'yes') === 'yes';
    }

    public function getCost(): int
    {
        return max(0, (int) Setting::get('lucky_wheel.cost', 100));
    }

    public function getDailyLimit(): int
    {
        return max(0, (int) Setting::get('lucky_wheel.daily_limit', 3));
    }

    public function getMinClass(): int
    {
        return max(0, (int) Setting::get('lucky_wheel.min_class', 0));
    }

    public function shouldShowProbability(): bool
    {
        return Setting::get('lucky_wheel.show_probability', 'no') === 'yes';
    }

    public function getRemainingTimes(User $user): int
    {
        $limit = $this->getDailyLimit();
        if ($limit === 0) {
            return PHP_INT_MAX;
        }

        $used = SpinLog::query()
            ->where('uid', $user->id)
            ->where('created_at', '>=', Carbon::today())
            ->count();

        return max(0, $limit - $used);
    }

    private function validateSpin(User $user): void
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException(nexus_trans('lucky-wheel.message.disabled'));
        }

        $user->checkIsNormal();

        if ((int) $user->class < $this->getMinClass()) {
            throw new \RuntimeException(nexus_trans('lucky-wheel.message.class_not_enough'));
        }

        if ($this->getRemainingTimes($user) <= 0) {
            throw new \RuntimeException(nexus_trans('lucky-wheel.message.daily_limit_reached'));
        }

        if ((float) $user->seedbonus < $this->getCost()) {
            throw new \RuntimeException(nexus_trans('lucky-wheel.message.not_enough_bonus'));
        }
    }

    private function pickPrize(Collection $prizes): Prize
    {
        $totalWeight = $prizes->sum(fn (Prize $prize) => (float) $prize->probability);
        if ($totalWeight <= 0) {
            throw new \RuntimeException(nexus_trans('lucky-wheel.message.invalid_probability'));
        }

        $rand = mt_rand(1, (int) round($totalWeight * 10000));
        $cursor = 0;

        foreach ($prizes as $prize) {
            $cursor += (int) round((float) $prize->probability * 10000);
            if ($rand <= $cursor) {
                return $prize;
            }
        }

        return $prizes->last();
    }

    private function deliverPrize(User $user, Prize $prize, float $seedbonus): array
    {
        $delivered = true;
        $content = (string) ($prize->reward_content ?? '');

        switch ($prize->reward_type) {
            case Prize::REWARD_TYPE_BONUS:
                $seedbonus = max(0, $seedbonus + (float) $prize->reward_value);
                $user->update(['seedbonus' => $seedbonus]);
                break;
            case Prize::REWARD_TYPE_UPLOAD:
                $bytes = (int) round((float) $prize->reward_value * 1024 * 1024 * 1024);
                if ($bytes !== 0) {
                    $uploaded = max(0, (int) $user->uploaded + $bytes);
                    $user->update(['uploaded' => $uploaded]);
                }
                break;
            case Prize::REWARD_TYPE_INVITE:
                $count = max(1, (int) $prize->reward_value);
                $hashes = $this->generateUniqueInviteHashes($count);
                $now = Carbon::now();
                $hasCreatedAt = $this->hasColumn('invites', 'created_at');
                $invites = [];
                foreach ($hashes as $hash) {
                    $invite = [
                        'inviter' => $user->id,
                        'invitee' => '',
                        'hash' => $hash,
                        'valid' => 0,
                        'expired_at' => $now->copy()->addDays(Invite::TEMPORARY_INVITE_VALID_DAYS),
                        'time_invited' => $now,
                    ];
                    if ($hasCreatedAt) {
                        $invite['created_at'] = $now;
                    }
                    $invites[] = $invite;
                }
                Invite::query()->insert($invites);
                $content = implode(',', $hashes);
                break;
            case Prize::REWARD_TYPE_NO_AD:
                $until = $this->extendDateTime($user->noaduntil, (float) $prize->reward_value, 'hours');
                $user->update(['noad' => 'yes', 'noaduntil' => $until]);
                break;
            case Prize::REWARD_TYPE_RAINBOW_ID:
                $until = $this->extendUserMeta($user, UserMeta::META_KEY_PERSONALIZED_USERNAME, (float) $prize->reward_value, 'days');
                $content = $until;
                break;
            case Prize::REWARD_TYPE_MEDAL:
                $medalId = (int) $prize->reward_value;
                $medal = Medal::query()->findOrFail($medalId);
                $exists = $user->valid_medals()->where('medal_id', $medalId)->exists();
                if ($exists) {
                    $content = 'already owned medal: ' . $medalId;
                    break;
                }
                $expireAt = $medal->duration > 0 ? Carbon::now()->addDays((int) $medal->duration)->toDateTimeString() : null;
                $bonusAdditionExpireAt = $medal->bonus_addition_duration > 0 ? Carbon::now()->addDays((int) $medal->bonus_addition_duration)->toDateTimeString() : null;
                $user->medals()->attach([
                    $medal->id => [
                        'expire_at' => $expireAt,
                        'bonus_addition_expire_at' => $bonusAdditionExpireAt,
                        'status' => UserMedal::STATUS_NOT_WEARING,
                    ],
                ]);
                $content = 'medal ID: ' . $medalId;
                break;
            case Prize::REWARD_TYPE_VIP:
                $until = $this->extendDateTime($user->vip_until, (float) $prize->reward_value, 'hours');
                $update = ['vip_added' => 'yes', 'vip_until' => $until];
                if ((int) $user->class < User::CLASS_VIP) {
                    $update['class'] = User::CLASS_VIP;
                }
                $user->update($update);
                break;
            default:
                $delivered = false;
        }

        if ($delivered) {
            clear_user_cache($user->id);
        }

        return [
            'delivered' => $delivered,
            'seedbonus' => $seedbonus,
            'content' => $content,
        ];
    }

    private function extendDateTime(mixed $currentValue, float $amount, string $unit): string
    {
        $amount = max(1, (int) round($amount));
        $current = $currentValue ? Carbon::parse($currentValue) : null;
        $base = $current && $current->gt(Carbon::now()) ? $current : Carbon::now();

        return match ($unit) {
            'days' => $base->addDays($amount)->toDateTimeString(),
            default => $base->addHours($amount)->toDateTimeString(),
        };
    }

    private function extendUserMeta(User $user, string $metaKey, float $amount, string $unit): string
    {
        $meta = UserMeta::query()
            ->where('uid', $user->id)
            ->where('meta_key', $metaKey)
            ->where('status', UserMeta::STATUS_NORMAL)
            ->first();

        $until = $this->extendDateTime($meta?->deadline, $amount, $unit);
        UserMeta::query()->updateOrCreate(
            ['uid' => $user->id, 'meta_key' => $metaKey, 'status' => UserMeta::STATUS_NORMAL],
            ['deadline' => $until, 'meta_value' => '']
        );

        return $until;
    }

    private function generateUniqueInviteHashes(int $count): array
    {
        $hashes = [];
        $attempts = 0;

        while (count($hashes) < $count) {
            if (++$attempts > 20) {
                throw new \RuntimeException('Generate invite hash failed.');
            }
            $need = $count - count($hashes);
            for ($i = 0; $i < $need; $i++) {
                $hash = bin2hex(random_bytes(16));
                $hashes[$hash] = $hash;
            }
            $exists = Invite::query()->whereIn('hash', array_values($hashes))->get(['hash']);
            foreach ($exists as $invite) {
                unset($hashes[$invite->hash]);
            }
        }

        return array_values($hashes);
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $columns = [];

        if (!isset($columns[$table])) {
            $connection = (new User())->getConnection();
            $schema = $connection->getSchemaBuilder();
            $columns[$table] = array_fill_keys($schema->getColumnListing($table), true);
        }

        return isset($columns[$table][$column]);
    }

    private function notifyManualPrize(User $user, Prize $prize, SpinLog $log): void
    {
        $admins = User::query()
            ->where('class', '>=', User::CLASS_ADMINISTRATOR)
            ->where('enabled', User::ENABLED_YES)
            ->orderByDesc('class')
            ->orderBy('id')
            ->limit(10)
            ->get(['id']);

        if ($admins->isEmpty()) {
            return;
        }

        $subject = sprintf('幸运大转盘待发奖：%s', $prize->name);
        $message = sprintf(
            "用户 %s(ID: %s) 在幸运大转盘抽中：%s\n中奖记录 ID：%s\n奖品类型：%s\n奖励数值：%s\n发放说明：%s\n请管理员人工处理后到后台中奖记录中确认。",
            $user->username,
            $user->id,
            $prize->name,
            $log->id,
            $prize->reward_type,
            $prize->reward_value,
            $prize->reward_content ?: '无'
        );

        foreach ($admins as $admin) {
            Message::add([
                'sender' => 0,
                'receiver' => $admin->id,
                'added' => Carbon::now(),
                'subject' => $subject,
                'msg' => $message,
            ]);
        }
    }
}