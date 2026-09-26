@extends('public.layout')

@section('content')
@php
    $badge = match ($order->payment_status) {
        'paid' => 'cust-badge-paid',
        'pending_payment', 'unpaid' => 'cust-badge-pending',
        'failed', 'refunded' => 'cust-badge-failed',
        default => 'cust-badge-default',
    };
@endphp

<div class="cust-shell">
    @include('public.commerce._account-shell')

    <div class="cust-main">
        <div class="cust-top">
            <div>
                <p class="site-kicker">Order detail</p>
                <h2>{{ $order->number }}</h2>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.55rem;">
                <a class="site-btn site-btn-outline" href="{{ route('site.account.orders', ['siteTenant' => $tenant->slug]) }}">All orders</a>
                @if ($order->payment_status === 'paid')
                    <a class="site-btn" href="{{ route('site.payment.invoice', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">Download invoice</a>
                @else
                    <a class="site-btn" href="{{ route('site.payment.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">Complete payment</a>
                @endif
            </div>
        </div>

        @if (session('status'))
            <p style="margin:0 0 1rem;">{{ session('status') }}</p>
        @endif

        <div class="cust-panel" style="margin-bottom:1rem;">
            <div class="cust-panel-h">
                <h3>Summary</h3>
                <span class="cust-badge {{ $badge }}">{{ str_replace('_', ' ', $order->payment_status) }}</span>
            </div>
            <div class="cust-grid">
                <div class="cust-meta">
                    <p>Payment method</p>
                    <strong>{{ strtoupper($order->payment_gateway) }}</strong>
                </div>
                <div class="cust-meta">
                    <p>Subtotal</p>
                    <strong>{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</strong>
                </div>
                <div class="cust-meta">
                    <p>GST ({{ number_format((float) $order->tax_rate, 0) }}%)</p>
                    <strong>{{ number_format($order->tax_amount, 2) }} {{ $order->currency }}</strong>
                </div>
                <div class="cust-meta">
                    <p>Total</p>
                    <strong>{{ number_format($order->total, 2) }} {{ $order->currency }}</strong>
                </div>
                <div class="cust-meta">
                    <p>Placed</p>
                    <strong>{{ $order->created_at->format('M j, Y H:i') }}</strong>
                </div>
                <div class="cust-meta">
                    <p>Paid at</p>
                    <strong>{{ $order->paid_at?->format('M j, Y H:i') ?? '—' }}</strong>
                </div>
            </div>
        </div>

        @if (is_array($order->shipping))
            <div class="cust-panel" style="margin-bottom:1rem;">
                <div class="cust-panel-h"><h3>Delivery address</h3></div>
                <div style="padding:1.15rem;font-size:.95rem;line-height:1.55;color:#334155;">
                    {{ $order->shipping['name'] ?? $customer->name }}<br>
                    {{ $order->shipping['address'] ?? '' }}<br>
                    @if (!empty($order->shipping['landmark'])){{ $order->shipping['landmark'] }}<br>@endif
                    {{ $order->shipping['pincode'] ?? '' }}
                    @if (!empty($order->shipping['phone'])) · {{ $order->shipping['phone'] }}@endif
                </div>
            </div>
        @endif

        <div class="cust-panel">
            <div class="cust-panel-h"><h3>Items</h3></div>
            <div class="cust-table-wrap">
                <table class="cust-table" style="min-width:28rem;">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td style="font-weight:600;">{{ number_format($item->line_total, 2) }} {{ $order->currency }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
