@extends('public.layout')

@section('content')
<div class="cust-shell">
    @include('public.commerce._account-shell')

    <div class="cust-main">
        <div class="cust-top">
            <div>
                <p class="site-kicker">Orders</p>
                <h2>Order history</h2>
            </div>
            <a class="site-btn site-btn-outline" href="{{ route('site.account.dashboard', ['siteTenant' => $tenant->slug]) }}">Dashboard</a>
        </div>

        <div class="cust-panel">
            <div class="cust-panel-h">
                <h3>All orders</h3>
                <span style="font-size:.85rem;color:#64748b;">{{ $orders->total() }} total</span>
            </div>

            @if ($orders->isEmpty())
                <div class="cust-empty">No orders yet.</div>
            @else
                <div class="cust-table-wrap">
                    <table class="cust-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Payment</th>
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
                                    </td>
                                    <td>{{ $order->created_at->format('M j, Y H:i') }}</td>
                                    <td>{{ $order->items_count }}</td>
                                    <td>
                                        <span class="cust-badge {{ $badge }}">{{ str_replace('_', ' ', $order->payment_status) }}</span>
                                        <div style="font-size:.75rem;color:#94a3b8;margin-top:.2rem;">{{ strtoupper($order->payment_gateway) }}</div>
                                    </td>
                                    <td style="font-weight:600;">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                                    <td style="white-space:nowrap;">
                                        <a class="site-btn site-btn-outline" href="{{ route('site.account.orders.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">Details</a>
                                        @if ($order->payment_status === 'paid')
                                            <a class="site-btn site-btn-outline" href="{{ route('site.payment.invoice', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">Invoice</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($orders->hasPages())
            <div style="margin-top:1rem;">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
