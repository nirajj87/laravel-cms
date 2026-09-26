@extends('public.layout')

@section('content')
<div class="cust-shell">
    @include('public.commerce._account-shell')

    <div class="cust-main">
        <div class="cust-top">
            <div>
                <p class="site-kicker">Account</p>
                <h2>Hello, {{ $customer->name }}</h2>
            </div>
            <a class="site-btn" href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">Continue shopping</a>
        </div>

        @if (session('status'))
            <p style="margin:0 0 1rem;">{{ session('status') }}</p>
        @endif

        <div class="cust-stats">
            <div class="cust-stat">
                <p>Total orders</p>
                <strong>{{ $stats['orders'] }}</strong>
            </div>
            <div class="cust-stat">
                <p>Paid</p>
                <strong>{{ $stats['paid'] }}</strong>
            </div>
            <div class="cust-stat">
                <p>Pending</p>
                <strong>{{ $stats['pending'] }}</strong>
            </div>
            <div class="cust-stat">
                <p>Total spent</p>
                <strong>{{ number_format($stats['spent'], 2) }} {{ $commerce['currency'] }}</strong>
            </div>
            <div class="cust-stat">
                <p>Cart</p>
                <strong>{{ $cartCount }} items</strong>
            </div>
        </div>

        <div class="cust-panel">
            <div class="cust-panel-h">
                <h3>Recent orders</h3>
                <a class="site-btn site-btn-outline" href="{{ route('site.account.orders', ['siteTenant' => $tenant->slug]) }}">View all</a>
            </div>

            @if ($orders->isEmpty())
                <div class="cust-empty">No orders yet. Browse the shop to place your first order.</div>
            @else
                <div class="cust-table-wrap">
                    <table class="cust-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                @php
                                    $badge = match ($order->payment_status) {
                                        'paid' => 'cust-badge-paid',
                                        'pending_payment', 'unpaid' => 'cust-badge-pending',
                                        'failed', 'refunded' => 'cust-badge-failed',
                                        default => 'cust-badge-default',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('site.account.orders.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}" style="font-weight:600;color:inherit;text-decoration:none;">{{ $order->number }}</a>
                                        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">{{ strtoupper($order->payment_gateway) }}</div>
                                    </td>
                                    <td>{{ $order->created_at->format('M j, Y') }}</td>
                                    <td>{{ $order->items_count }}</td>
                                    <td><span class="cust-badge {{ $badge }}">{{ str_replace('_', ' ', $order->payment_status) }}</span></td>
                                    <td style="font-weight:600;">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                                    <td>
                                        <a class="site-btn site-btn-outline" href="{{ route('site.account.orders.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
