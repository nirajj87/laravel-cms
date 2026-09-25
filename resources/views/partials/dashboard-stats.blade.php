@php
    /** @var list<array{label: string, value: int|float, hint?: string}> $cards */
    $cards = $cards ?? [];
    $chart = $chart ?? ['labels' => [], 'dates' => [], 'values' => [], 'total' => 0];
    $chartValues = array_values(array_map('intval', $chart['values'] ?? []));
    $chartLabels = array_values($chart['labels'] ?? []);
    $chartDates = array_values($chart['dates'] ?? []);
    $chartTotal = (int) ($chart['total'] ?? array_sum($chartValues));
    $chartMax = max(1, ...($chartValues ?: [0]));
    $count = max(count($chartValues), 1);

    $W = 720;
    $H = 260;
    $padL = 40;
    $padR = 20;
    $padT = 28;
    $padB = 44;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;
    $gap = 14;
    $barW = ($plotW - ($gap * ($count - 1))) / $count;
    $yTicks = array_values(array_unique([0, (int) max(1, ceil($chartMax / 2)), $chartMax]));
@endphp

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
    @foreach ($cards as $card)
        <div class="card relative overflow-hidden p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold tabular-nums"
                       x-data="statCounter({{ (int) $card['value'] }})"
                       x-text="display"
                       x-cloak>{{ (int) $card['value'] }}</p>
                    @if (! empty($card['hint']))
                        <p class="mt-1 text-xs text-slate-500">{{ $card['hint'] }}</p>
                    @endif
                </div>
                @if (! empty($card['icon']))
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-800">
                        <x-icon :name="$card['icon']" class="h-5 w-5" />
                    </span>
                @endif
            </div>
        </div>
    @endforeach
</div>

<section class="card mt-6 overflow-hidden">
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-stone-100 px-5 py-4">
        <div>
            <h2 class="font-semibold text-slate-900">Posts this week</h2>
            <p class="mt-0.5 text-sm text-slate-500">Created in the last 7 days</p>
        </div>
        <div class="flex items-baseline gap-6">
            <div class="text-right">
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Total</p>
                <p class="text-2xl font-semibold tabular-nums text-teal-800">{{ $chartTotal }}</p>
            </div>
            <div class="text-right">
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Daily avg</p>
                <p class="text-2xl font-semibold tabular-nums text-slate-800">{{ number_format($chartTotal / 7, 1) }}</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-5">
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Posts created each day this week">
            <defs>
                <linearGradient id="postBar" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2dd4bf"/>
                    <stop offset="100%" stop-color="#0f766e"/>
                </linearGradient>
                <linearGradient id="postBarMuted" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#e7e5e4"/>
                    <stop offset="100%" stop-color="#d6d3d1"/>
                </linearGradient>
            </defs>

            @foreach ($yTicks as $tick)
                @php $gy = $padT + $plotH - (($tick / $chartMax) * $plotH); @endphp
                <line x1="{{ $padL }}" y1="{{ round($gy, 1) }}" x2="{{ $W - $padR }}" y2="{{ round($gy, 1) }}"
                      stroke="#e7e5e4" stroke-width="1" stroke-dasharray="{{ $tick === 0 ? '0' : '5 5' }}"/>
                <text x="{{ $padL - 12 }}" y="{{ round($gy + 4, 1) }}" text-anchor="end" fill="#a8a29e"
                      font-size="12" font-family="ui-sans-serif, system-ui, sans-serif">{{ $tick }}</text>
            @endforeach

            @foreach ($chartValues as $i => $value)
                @php
                    $x = $padL + ($i * ($barW + $gap));
                    $barH = $value > 0
                        ? max(10, ($value / $chartMax) * $plotH)
                        : 6;
                    $y = $padT + $plotH - $barH;
                    $radius = min(8, $barW / 2);
                @endphp
                <g>
                    <title>{{ ($chartDates[$i] ?? $chartLabels[$i] ?? 'Day').': '.$value.' posts' }}</title>
                    <rect x="{{ round($x, 1) }}" y="{{ round($y, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($barH, 1) }}"
                          rx="{{ $radius }}" ry="{{ $radius }}"
                          fill="{{ $value > 0 ? 'url(#postBar)' : 'url(#postBarMuted)' }}"/>
                    @if ($value > 0)
                        <text x="{{ round($x + $barW / 2, 1) }}" y="{{ round($y - 8, 1) }}" text-anchor="middle"
                              fill="#0f766e" font-size="13" font-weight="700"
                              font-family="ui-sans-serif, system-ui, sans-serif">{{ $value }}</text>
                    @endif
                    <text x="{{ round($x + $barW / 2, 1) }}" y="{{ $H - 18 }}" text-anchor="middle"
                          fill="#44403c" font-size="12" font-weight="600"
                          font-family="ui-sans-serif, system-ui, sans-serif">{{ $chartLabels[$i] ?? '' }}</text>
                    <text x="{{ round($x + $barW / 2, 1) }}" y="{{ $H - 4 }}" text-anchor="middle"
                          fill="#a8a29e" font-size="10"
                          font-family="ui-sans-serif, system-ui, sans-serif">{{ $chartDates[$i] ?? '' }}</text>
                </g>
            @endforeach
        </svg>
    </div>
</section>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('statCounter', (target) => ({
                    display: 0,
                    init() {
                        const goal = Number(target) || 0;
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || goal === 0) {
                            this.display = goal;
                            return;
                        }
                        const start = performance.now();
                        const duration = 900;
                        const tick = (now) => {
                            const progress = Math.min(1, (now - start) / duration);
                            this.display = Math.round(goal * (1 - Math.pow(1 - progress, 3)));
                            if (progress < 1) requestAnimationFrame(tick);
                        };
                        requestAnimationFrame(tick);
                    },
                }));
            });
        </script>
    @endpush
@endonce
