<?php

require '../include/bittorrent.php';

dbconn();
loggedinorreturn();
parked();

function lucky_wheel_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function lucky_wheel_times_text(int $value): string
{
    return $value >= 1000000 ? nexus_trans('lucky-wheel.prize.unlimited') : (string) $value;
}

$title = nexus_trans('lucky-wheel.title');
$user = \App\Models\User::query()->findOrFail($CURUSER['id']);
$service = app(\NexusPlugin\LuckyWheel\Services\LuckyWheelService::class);
$config = $service->getPublicConfig($user);
$prizes = $service->listPublicPrizes();
$logs = $service->listRecentLogs();
$myLogs = $service->listUserLogs($user);

stdhead($title);
begin_main_frame();
?>
<style>
    .lucky-wheel-wrap { color: #3a2a11; }
    .lucky-wheel-card { background: #fffaf0; border: 1px solid #f5c16c; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    .lucky-wheel-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .lucky-wheel-prize { min-height: 92px; border-radius: 8px; border: 2px solid #f5c16c; background: #fffdf7; padding: 10px; display: flex; flex-direction: column; justify-content: center; transition: all .2s ease; }
    .lucky-wheel-prize.active { border-color: #e67e22; background: #fff0cf; box-shadow: 0 0 0 3px rgba(230, 126, 34, .16); transform: translateY(-2px); }
    .lucky-wheel-spin { border: 0; background: #f08c00; color: #fff; font-weight: 700; cursor: pointer; }
    .lucky-wheel-spin:disabled { cursor: not-allowed; opacity: .65; }
    .lucky-wheel-meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
    .lucky-wheel-meta-item, .lucky-wheel-log-item { background: #fffdf7; border: 1px solid #f5d28d; border-radius: 6px; padding: 10px; }
    .lucky-wheel-muted { color: #8a6a43; }
    .lucky-wheel-error, .lucky-wheel-success { padding: 10px; border-radius: 6px; margin-bottom: 12px; display: none; }
    .lucky-wheel-error { background: #fdecea; color: #b42318; }
    .lucky-wheel-success { background: #ecfdf3; color: #027a48; }
    @media (max-width: 768px) {
        .lucky-wheel-meta { grid-template-columns: repeat(2, 1fr); }
        .lucky-wheel-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="lucky-wheel-wrap">
    <div class="lucky-wheel-card">
        <h1><?php echo lucky_wheel_e($title); ?></h1>
        <p class="lucky-wheel-muted"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.subtitle')); ?></p>
        <div id="luckyWheelError" class="lucky-wheel-error"></div>
        <div id="luckyWheelSuccess" class="lucky-wheel-success"></div>
        <div class="lucky-wheel-meta">
            <div class="lucky-wheel-meta-item" id="luckyWheelBonus"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.current_bonus')); ?>: <?php echo number_format((float) $user->seedbonus, 1); ?></div>
            <div class="lucky-wheel-meta-item"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.cost')); ?>: <?php echo (int) $config['cost']; ?></div>
            <div class="lucky-wheel-meta-item"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.daily_limit')); ?>: <?php echo $config['daily_limit'] === 0 ? lucky_wheel_e(nexus_trans('lucky-wheel.prize.unlimited')) : (int) $config['daily_limit']; ?></div>
            <div class="lucky-wheel-meta-item" id="luckyWheelRemaining"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.remaining')); ?>: <?php echo lucky_wheel_e(lucky_wheel_times_text((int) $config['remaining_times'])); ?></div>
        </div>

        <?php if (!$config['enabled']) { ?>
            <div class="lucky-wheel-error" style="display:block"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.message.disabled')); ?></div>
        <?php } elseif ($prizes->isEmpty()) { ?>
            <div class="lucky-wheel-error" style="display:block"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.message.no_prize')); ?></div>
        <?php } ?>

        <div class="lucky-wheel-grid">
            <?php foreach ($prizes as $prize) { ?>
                <div class="lucky-wheel-prize" data-prize-id="<?php echo (int) $prize->id; ?>">
                    <strong><?php echo lucky_wheel_e($prize->name); ?></strong>
                    <?php if ((string) $prize->description !== '') { ?>
                        <span class="lucky-wheel-muted"><?php echo lucky_wheel_e($prize->description); ?></span>
                    <?php } ?>
                    <?php if (!empty($config['show_probability'])) { ?>
                        <span class="lucky-wheel-muted"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.probability')); ?>: <?php echo lucky_wheel_e($prize->probability); ?></span>
                    <?php } ?>
                </div>
            <?php } ?>
            <button class="lucky-wheel-prize lucky-wheel-spin" id="luckyWheelSpinButton" type="button"<?php echo (!$config['enabled'] || $prizes->isEmpty()) ? ' disabled' : ''; ?>>
                <?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.spin_now')); ?>
            </button>
        </div>
    </div>

    <div class="lucky-wheel-card">
        <h2><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.my_records')); ?></h2>
        <div id="luckyWheelMyLogs">
        <?php if ($myLogs->isEmpty()) { ?>
            <div class="lucky-wheel-log-item lucky-wheel-muted"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.no_records')); ?></div>
        <?php } else { ?>
            <?php foreach ($myLogs as $log) { ?>
                <div class="lucky-wheel-log-item"><?php echo lucky_wheel_e($log->created_at); ?> - <?php echo lucky_wheel_e($log->prize_name); ?> - <?php echo lucky_wheel_e($log->delivery_status); ?></div>
            <?php } ?>
        <?php } ?>
        </div>
    </div>

    <div class="lucky-wheel-card">
        <h2><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.latest_records')); ?></h2>
        <div id="luckyWheelLatestLogs">
        <?php if ($logs->isEmpty()) { ?>
            <div class="lucky-wheel-log-item lucky-wheel-muted"><?php echo lucky_wheel_e(nexus_trans('lucky-wheel.panel.no_records')); ?></div>
        <?php } else { ?>
            <?php foreach ($logs as $log) { ?>
                <div class="lucky-wheel-log-item"><?php echo lucky_wheel_e($log->user->username ?? ('#' . $log->uid)); ?> - <?php echo lucky_wheel_e($log->prize_name); ?> - <?php echo lucky_wheel_e($log->created_at); ?></div>
            <?php } ?>
        <?php } ?>
        </div>
    </div>
</div>

<script>
    (function () {
        const spinButton = document.getElementById('luckyWheelSpinButton');
        const errorBox = document.getElementById('luckyWheelError');
        const successBox = document.getElementById('luckyWheelSuccess');
        const remainingBox = document.getElementById('luckyWheelRemaining');
        const bonusBox = document.getElementById('luckyWheelBonus');
        const myLogsBox = document.getElementById('luckyWheelMyLogs');
        const latestLogsBox = document.getElementById('luckyWheelLatestLogs');
        const prizes = Array.from(document.querySelectorAll('.lucky-wheel-prize[data-prize-id]'));
        const remainingLabel = <?php echo json_encode(nexus_trans('lucky-wheel.panel.remaining'), JSON_UNESCAPED_UNICODE); ?>;
        const bonusLabel = <?php echo json_encode(nexus_trans('lucky-wheel.panel.current_bonus'), JSON_UNESCAPED_UNICODE); ?>;
        const unlimitedLabel = <?php echo json_encode(nexus_trans('lucky-wheel.prize.unlimited'), JSON_UNESCAPED_UNICODE); ?>;
        const spinNowLabel = <?php echo json_encode(nexus_trans('lucky-wheel.panel.spin_now'), JSON_UNESCAPED_UNICODE); ?>;
        let spinning = false;

        if (!spinButton) return;

        function showMessage(el, text) {
            errorBox.style.display = 'none';
            successBox.style.display = 'none';
            el.textContent = text;
            el.style.display = 'block';
        }

        function escapeHtml(text) {
            return String(text ?? '').replace(/[&<>"']/g, function (char) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
            });
        }

        function addLog(container, html) {
            if (!container) return;
            container.querySelectorAll('.lucky-wheel-muted').forEach(item => item.remove());
            const item = document.createElement('div');
            item.className = 'lucky-wheel-log-item';
            item.innerHTML = html;
            container.prepend(item);
        }

        function updateLogs(data) {
            if (!data.log) return;
            const createdAt = escapeHtml(data.log.created_at);
            const prizeName = escapeHtml(data.log.prize_name || data.prize.name);
            const status = escapeHtml(data.log.delivery_status || data.prize.delivery_status);
            const username = escapeHtml(data.log.username || ('#' + data.log.uid));
            addLog(myLogsBox, createdAt + ' - ' + prizeName + ' - ' + status);
            addLog(latestLogsBox, username + ' - ' + prizeName + ' - ' + createdAt);
        }

        function animateToPrize(prizeId) {
            return new Promise(resolve => {
                if (prizes.length === 0) {
                    resolve();
                    return;
                }
                let index = 0;
                let rounds = Math.max(16, prizes.length * 4);
                const timer = setInterval(function () {
                    prizes.forEach(item => item.classList.remove('active'));
                    prizes[index % prizes.length].classList.add('active');
                    index++;
                    rounds--;
                    if (rounds <= 0) {
                        clearInterval(timer);
                        prizes.forEach(item => item.classList.remove('active'));
                        const target = document.querySelector('[data-prize-id="' + prizeId + '"]');
                        if (target) target.classList.add('active');
                        setTimeout(resolve, 220);
                    }
                }, 85);
            });
        }

        spinButton.addEventListener('click', async function () {
            if (spinning || spinButton.disabled) return;
            spinning = true;
            spinButton.disabled = true;
            spinButton.textContent = <?php echo json_encode(nexus_trans('lucky-wheel.panel.spin_now') . '...', JSON_UNESCAPED_UNICODE); ?>;
            errorBox.style.display = 'none';
            successBox.style.display = 'none';
            try {
                const response = await fetch('takeluckywheel.php', {
                    method: 'POST',
                    headers: {'Accept': 'application/json'},
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Spin failed, please try again later');
                }
                await animateToPrize(data.prize.id);
                remainingBox.textContent = remainingLabel + ': ' + (data.remaining_times > 1000000 ? unlimitedLabel : data.remaining_times);
                if (bonusBox && data.seedbonus !== undefined) {
                    bonusBox.textContent = bonusLabel + ': ' + Number(data.seedbonus).toFixed(1);
                }
                updateLogs(data);
                showMessage(successBox, data.message + ': ' + data.prize.name);
            } catch (error) {
                showMessage(errorBox, error.message);
            } finally {
                spinning = false;
                spinButton.disabled = false;
                spinButton.textContent = spinNowLabel;
            }
        });
    })();
</script>
<?php
end_main_frame();
stdfoot();
