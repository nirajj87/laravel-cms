@extends('layouts.app')
@section('title', $customer->name)
@section('kicker', 'WooCommerce · Customers')
@section('heading', $customer->name)

@section('content')
    <style>
        .woo-panel{background:#fff;border:1px solid #e7e5e4;border-radius:16px;padding:1.25rem 1.35rem;}
        .woo-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}
        .woo-label{margin:0;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;}
        .woo-value{margin:.3rem 0 0;font-size:.95rem;color:#0f172a;word-break:break-word;}
        .woo-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.85rem;}
        .woo-order{border-bottom:1px solid #f5f5f4;padding:1rem 0;}
        .woo-order:last-child{border-bottom:0;padding-bottom:0;}
        .woo-badge{display:inline-flex;padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;background:#ecfdf5;color:#065f46;}
    </style>

    <div class="mb-4 flex flex-wrap gap-2">
        <a class="btn btn-secondary" href="{{ route('tenant.commerce.customers') }}">All customers</a>
        <a class="btn btn-secondary" href="{{ route('tenant.commerce.orders') }}">Orders</a>
    </div>

    <div class="woo-panel mb-5">
        <div class="woo-grid">
            <div>
                <p class="woo-label">Email</p>
                <p class="woo-value woo-mono">{{ $customer->email }}</p>
            </div>
            <div>
                <p class="woo-label">Mobile</p>
                <p class="woo-value">{{ $customer->phone ?: '—' }}</p>
            </div>
            <div>
                <p class="woo-label">Issued password</p>
                <p class="woo-value">
                    @if ($customer->issued_password)
                        <span class="woo-mono rounded-md bg-stone-100 px-2 py-1">{{ $customer->issued_password }}</span>
                    @else
                        —
                    @endif
                </p>
            </div>
            <div>
                <p class="woo-label">Joined</p>
                <p class="woo-value">{{ $customer->created_at->format('M j, Y H:i') }}</p>
            </div>
            <div>
                <p class="woo-label">Pincode</p>
                <p class="woo-value">{{ $customer->pincode ?: '—' }}</p>
            </div>
            <div>
                <p class="woo-label">Landmark</p>
                <p class="woo-value">{{ $customer->landmark ?: '—' }}</p>
            </div>
            <div style="grid-column:1/-1;">
                <p class="woo-label">Address</p>
                <p class="woo-value">{{ $customer->address ?: '—' }}</p>
            </div>
            <div style="grid-column:1/-1;">
                <p class="woo-label">Customer login</p>
                <p class="woo-value">
                    <a class="text-teal-800 hover:underline" href="{{ route('site.account.login', ['siteTenant' => current_tenant()->slug]) }}" target="_blank" rel="noopener">
                        {{ route('site.account.login', ['siteTenant' => current_tenant()->slug]) }}
                    </a>
                </p>
            </div>
        </div>
    </div>

    <div class="woo-panel">
        <div class="mb-2 flex items-center justify-between gap-2">
            <h2 class="m-0 text-base font-semibold text-slate-900">Order history</h2>
            <span class="woo-badge">{{ $customer->orders->count() }} orders</span>
        </div>

        @forelse ($customer->orders as $order)
            <article class="woo-order">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <a class="font-semibold text-teal-800 hover:underline" href="{{ route('tenant.commerce.orders.show', $order) }}">{{ $order->number }}</a>
                    <span class="text-sm text-slate-500">
                        {{ $order->created_at->format('M j, Y H:i') }} ·
                        {{ str_replace('_', ' ', $order->payment_status) }} ·
                        {{ number_format($order->total, 2) }} {{ $order->currency }}
                    </span>
                </div>
                @if (is_array($order->shipping))
                    <p class="mb-2 text-xs text-slate-500">
                        Ship to: {{ $order->shipping['name'] ?? '' }},
                        {{ $order->shipping['address'] ?? '' }},
                        {{ $order->shipping['pincode'] ?? '' }}
                        @if (!empty($order->shipping['landmark'])) · {{ $order->shipping['landmark'] }} @endif
                    </p>
                @endif
                <ul class="m-0 list-disc space-y-1 pl-5 text-sm text-slate-700">
                    @foreach ($order->items as $item)
                        <li>{{ $item->title }} × {{ $item->quantity }} @ {{ number_format($item->unit_price, 2) }} = {{ number_format($item->line_total, 2) }} {{ $order->currency }}</li>
                    @endforeach
                </ul>
            </article>
        @empty
            <p class="m-0 text-slate-500">No orders yet.</p>
        @endforelse
    </div>
@endsection
