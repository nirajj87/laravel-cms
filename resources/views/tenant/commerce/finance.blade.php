@extends('layouts.app')
@section('title', 'Finance')
@section('kicker', 'WooCommerce')
@section('heading', 'Finance')

@section('content')
@php
    $chartValues = array_values(array_map('floatval', $chart['values'] ?? []));
    $chartLabels = array_values($chart['labels'] ?? []);
    $chartDates = array_values($chart['dates'] ?? []);
    $chartTotal = (float) ($chart['total'] ?? array_sum($chartValues));
    $chartMax = max(1, ...($chartValues ?: [0]));
    $count = max(count($chartValues), 1);
    $W = 720; $H = 260; $padL = 40; $padR = 20; $padT = 28; $padB = 44;
    $plotW = $W - $padL - $padR; $plotH = $H - $padT - $padB; $gap = 14;
    $barW = ($plotW - ($gap * ($count - 1))) / $count;
@endphp

<style>
.fin-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:1.25rem;}
.fin-card{background:#fff;border:1px solid #e7e5e4;border-radius:16px;padding:1.1rem 1.2rem;}
.fin-card p{margin:0;font-size:.75rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;}
.fin-card strong{display:block;margin-top:.4rem;font-size:1.55rem;color:#0f172a;}
.fin-card span{display:block;margin-top:.25rem;font-size:.78rem;color:#94a3b8;}
.fin-panel{background:#fff;border:1px solid #e7e5e4;border-radius:16px;overflow:hidden;}
</style>

<div class="fin-grid">
@foreach ($cards as $card)
    <div class="fin-card">
        <p>{{ $card['label'] }}</p>
        <strong>{{ is_float($card['value']) || str_contains((string) $card['value'], '.') ? number_format((float) $card['value'], 2) : number_format((int) $card['value']) }}</strong>
        @if (!empty($card['hint']))<span>{{ $card['hint'] }}</span>@endif
    </div>
@endforeach
</div>

<div class="fin-panel mb-5">
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-stone-100 px-5 py-4">
        <div>
            <h2 class="m-0 font-semibold text-slate-900">Revenue · last 7 days</h2>
            <p class="m-0 mt-1 text-sm text-slate-500">Paid orders including GST</p>
        </div>
        <div class="text-right">
            <p class="m-0 text-[11px] uppercase tracking-wide text-slate-400">Week total</p>
            <p class="m-0 text-2xl font-semibold text-teal-800">{{ number_format($chartTotal, 2) }} {{ $commerce['currency'] }}</p>
        </div>
    </div>
    <div class="p-4 sm:p-5">
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Revenue chart">
            <defs>
                <linearGradient id="finBar" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2dd4bf"/>
                    <stop offset="100%" stop-color="#0f766e"/>
                </linearGradient>
            </defs>
            @foreach ($chartValues as $i => $value)
                @php
                    $x = $padL + ($i * ($barW + $gap));
                    $h = ($value / $chartMax) * $plotH;
                    $y = $padT + $plotH - $h;
                @endphp
                <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barW }}" height="{{ max($h, 2) }}" rx="6" fill="url(#finBar)" opacity="{{ $value > 0 ? 1 : .35 }}"/>
                <text x="{{ $x + $barW/2 }}" y="{{ $H - 18 }}" text-anchor="middle" font-size="11" fill="#78716c">{{ $chartLabels[$i] ?? '' }}</text>
                <text x="{{ $x + $barW/2 }}" y="{{ max(14, $y - 6) }}" text-anchor="middle" font-size="10" fill="#0f766e">{{ number_format($value, 0) }}</text>
            @endforeach
        </svg>
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-2">
    <div class="fin-panel p-5">
        <h2 class="mb-3 mt-0 text-base font-semibold">By payment method</h2>
        @forelse ($gateways as $row)
            <div class="mb-3 flex items-center justify-between gap-3 border-b border-stone-100 pb-3 last:mb-0 last:border-0 last:pb-0">
                <div>
                    <p class="m-0 font-medium uppercase">{{ $row->payment_gateway }}</p>
                    <p class="m-0 text-xs text-slate-500">{{ $row->cnt }} payments</p>
                </div>
                <p class="m-0 font-semibold">{{ number_format((float) $row->amount, 2) }} {{ $commerce['currency'] }}</p>
            </div>
        @empty
            <p class="m-0 text-slate-500">No paid orders yet.</p>
        @endforelse
    </div>

    <div class="fin-panel p-5">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="m-0 text-base font-semibold">Recent paid</h2>
            <a class="text-sm text-teal-800 hover:underline" href="{{ route('tenant.commerce.payments') }}">Payment history</a>
        </div>
        @forelse ($recent as $order)
            <div class="mb-3 flex items-center justify-between gap-3 border-b border-stone-100 pb-3 last:mb-0 last:border-0 last:pb-0">
                <div>
                    <a class="font-medium text-teal-800 hover:underline" href="{{ route('tenant.commerce.orders.show', $order) }}">{{ $order->number }}</a>
                    <p class="m-0 text-xs text-slate-500">{{ $order->customer?->name }} · GST {{ number_format($order->tax_amount, 2) }}</p>
                </div>
                <p class="m-0 font-semibold">{{ number_format($order->total, 2) }}</p>
            </div>
        @empty
            <p class="m-0 text-slate-500">No payments yet.</p>
        @endforelse
    </div>
</div>
@endsection
