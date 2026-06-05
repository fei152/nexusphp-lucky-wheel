<?php

require '../include/bittorrent.php';

dbconn();
loggedinorreturn();
parked();

header('Content-Type: application/json; charset=utf-8');

function lucky_wheel_json_message(string $message): string
{
    $messages = [
        'lucky-wheel.message.disabled' => '幸运大转盘暂未开启',
        'lucky-wheel.message.class_not_enough' => '当前用户等级不足，不能参与抽奖',
        'lucky-wheel.message.daily_limit_reached' => '今天的抽奖次数已经用完了',
        'lucky-wheel.message.not_enough_bonus' => '魔力值不足',
        'lucky-wheel.message.no_prize' => '当前没有可抽取的奖品',
        'lucky-wheel.message.invalid_probability' => '奖品概率配置无效',
        'lucky-wheel.message.spin_success' => '抽奖成功',
    ];

    return $messages[$message] ?? $message;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '请求方式不正确'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $user = \App\Models\User::query()->findOrFail($CURUSER['id']);
    $service = app(\NexusPlugin\LuckyWheel\Services\LuckyWheelService::class);
    $result = $service->spin($user);
    $result['success'] = true;
    $result['message'] = lucky_wheel_json_message((string) $result['message']);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => lucky_wheel_json_message($throwable->getMessage()),
    ], JSON_UNESCAPED_UNICODE);
}
