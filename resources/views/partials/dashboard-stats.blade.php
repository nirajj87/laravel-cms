@php
    /** @var list<array{label: string, value: int|float, hint?: string}> $cards */
    $cards = $cards ?? [];
    $chart = $chart ?? ['labels' => [], 'values' => []];
@endphp

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
    @foreach ($cards as $card)
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums"
               x-data="statCounter({{ (int) $card['value'] }})"
               x-text="display"
               x-cloak>{{ (int) $card['value'] }}</p>
            @if (! empty($card['hint']))
                <p class="mt-1 text-xs text-slate-500">{{ $card['hint'] }}</p>
            @endif
        </div>
    @endforeach
</div>

<section class="card mt-6 p-5">
    <div class="mb-4 flex items-end justify-between gap-3">
        <div>
            <h2 class="font-semibold">Posts this week</h2>
            <p class="text-sm text-slate-500">New posts created in the last 7 days</p>
        </div>
    </div>
    <div class="relative h-56" x-data="weekChart(@js($chart))" x-init="draw()">
        <canvas x-ref="canvas" class="h-full w-full" aria-label="Posts this week"></canvas>
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

                Alpine.data('weekChart', (payload) => ({
                    labels: payload.labels || [],
                    values: payload.values || [],
                    draw() {
                        const canvas = this.$refs.canvas;
                        if (! canvas) return;
                        const ctx = canvas.getContext('2d');
                        const dpr = window.devicePixelRatio || 1;
                        const width = canvas.clientWidth;
                        const height = canvas.clientHeight;
                        canvas.width = width * dpr;
                        canvas.height = height * dpr;
                        ctx.scale(dpr, dpr);
                        ctx.clearRect(0, 0, width, height);

                        const values = this.values.map((v) => Number(v) || 0);
                        const max = Math.max(1, ...values);
                        const pad = { top: 16, right: 12, bottom: 28, left: 8 };
                        const chartW = width - pad.left - pad.right;
                        const chartH = height - pad.top - pad.bottom;
                        const gap = 10;
                        const barW = Math.max(8, (chartW - gap * (values.length - 1)) / Math.max(values.length, 1));
                        const primary = '#0f766e';

                        values.forEach((value, index) => {
                            const x = pad.left + index * (barW + gap);
                            const h = (value / max) * chartH;
                            const y = pad.top + chartH - h;
                            ctx.fillStyle = primary;
                            ctx.globalAlpha = 0.85;
                            ctx.beginPath();
                            const r = 6;
                            ctx.moveTo(x, y + r);
                            ctx.arcTo(x, y, x + barW, y, r);
                            ctx.arcTo(x + barW, y, x + barW, y + h, r);
                            ctx.lineTo(x + barW, y + h);
                            ctx.lineTo(x, y + h);
                            ctx.closePath();
                            ctx.fill();
                            ctx.globalAlpha = 1;
                            ctx.fillStyle = '#64748b';
                            ctx.font = '12px Instrument Sans, ui-sans-serif, system-ui, sans-serif';
                            ctx.textAlign = 'center';
                            ctx.fillText(this.labels[index] || '', x + barW / 2, height - 8);
                        });
                    },
                }));
            });
        </script>
    @endpush
@endonce
