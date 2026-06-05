<style>
    .lucky-wheel-wrap { color: #3a2a11; }
    .lucky-wheel-card { background: #fffaf0; border: 1px solid #f5c16c; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    .lucky-wheel-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .lucky-wheel-prize { min-height: 92px; border-radius: 8px; border: 2px solid #f5c16c; background: #fffdf7; padding: 10px; display: flex; flex-direction: column; justify-content: center; }
    .lucky-wheel-prize.active { border-color: #e67e22; background: #fff0cf; }
    .lucky-wheel-spin { border: 0; background: #f08c00; color: #fff; font-weight: 700; cursor: pointer; }
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

@php($unlimited = nexus_trans('lucky-wheel.prize.unlimited'))

<div class="lucky-wheel-wrap">
    <div class="lucky-wheel-card">
        <h1>{{ nexus_trans('lucky-wheel.title') }}</h1>
        <p class="lucky-wheel-muted">{{ nexus_trans('lucky-wheel.subtitle') }}</p>
        <div id="luckyWheelError" class="lucky-wheel-error"></div>
        <div id="luckyWheelSuccess" class="lucky-wheel-success"></div>
        <div class="lucky-wheel-meta">
            <div class="lucky-wheel-meta-item">{{ nexus_trans('lucky-wheel.panel.current_bonus') }}: {{ number_format($user->seedbonus, 1) }}</div>
            <div class="lucky-wheel-meta-item">{{ nexus_trans('lucky-wheel.panel.cost') }}: {{ $config['cost'] }}</div>
            <div class="lucky-wheel-meta-item">{{ nexus_trans('lucky-wheel.panel.daily_limit') }}: {{ $config['daily_limit'] === PHP_INT_MAX ? $unlimited : $config['daily_limit'] }}</div>
            <div class="lucky-wheel-meta-item" id="luckyWheelRemaining">{{ nexus_trans('lucky-wheel.panel.remaining') }}: {{ $config['remaining_times'] === PHP_INT_MAX ? $unlimited : $config['remaining_times'] }}</div>
        </div>
        <div class="lucky-wheel-grid">
            @foreach($prizes as $prize)
                <div class="lucky-wheel-prize" data-prize-id="{{ $prize->id }}">
                    <strong>{{ $prize->name }}</strong>
                    <span class="lucky-wheel-muted">{{ $prize->description }}</span>
                    <span class="lucky-wheel-muted">{{ nexus_trans('lucky-wheel.panel.probability') }}: {{ $prize->probability }}</span>
                </div>
            @endforeach
            <button class="lucky-wheel-prize lucky-wheel-spin" id="luckyWheelSpinButton" type="button">{{ nexus_trans('lucky-wheel.panel.spin_now') }}</button>
        </div>
    </div>

    <div class="lucky-wheel-card">
        <h2>{{ nexus_trans('lucky-wheel.panel.my_records') }}</h2>
        @forelse($myLogs as $log)
            <div class="lucky-wheel-log-item">{{ $log->created_at }} - {{ $log->prize_name }} - {{ $log->delivery_status }}</div>
        @empty
            <div class="lucky-wheel-log-item lucky-wheel-muted">{{ nexus_trans('lucky-wheel.panel.no_records') }}</div>
        @endforelse
    </div>

    <div class="lucky-wheel-card">
        <h2>{{ nexus_trans('lucky-wheel.panel.latest_records') }}</h2>
        @forelse($logs as $log)
            <div class="lucky-wheel-log-item">{{ $log->user?->username ?? ('#' . $log->uid) }} - {{ $log->prize_name }} - {{ $log->created_at }}</div>
        @empty
            <div class="lucky-wheel-log-item lucky-wheel-muted">{{ nexus_trans('lucky-wheel.panel.no_records') }}</div>
        @endforelse
    </div>
</div>

<script>
    (function () {
        const spinButton = document.getElementById('luckyWheelSpinButton');
        const errorBox = document.getElementById('luckyWheelError');
        const successBox = document.getElementById('luckyWheelSuccess');
        const remainingBox = document.getElementById('luckyWheelRemaining');
        const prizes = Array.from(document.querySelectorAll('.lucky-wheel-prize[data-prize-id]'));
        let spinning = false;

        function showMessage(el, text) {
            errorBox.style.display = 'none';
            successBox.style.display = 'none';
            el.textContent = text;
            el.style.display = 'block';
        }

        spinButton.addEventListener('click', async function () {
            if (spinning) return;
            spinning = true;
            spinButton.disabled = true;
            try {
                const response = await fetch('/web/lucky-wheel/spin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({})
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Spin failed');
                }
                prizes.forEach(item => item.classList.remove('active'));
                const target = document.querySelector(`[data-prize-id="${data.prize.id}"]`);
                if (target) target.classList.add('active');
                remainingBox.textContent = '{{ nexus_trans('lucky-wheel.panel.remaining') }}: ' + (data.remaining_times > 1000000 ? '{{ $unlimited }}' : data.remaining_times);
                showMessage(successBox, `${data.message}: ${data.prize.name}`);
            } catch (error) {
                showMessage(errorBox, error.message);
            } finally {
                spinning = false;
                spinButton.disabled = false;
            }
        });
    })();
</script>
