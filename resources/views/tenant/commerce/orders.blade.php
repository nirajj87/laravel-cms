@extends('layouts.app')
@section('title', 'Orders')
@section('kicker', 'WooCommerce')
@section('heading', 'Orders')

@section('content')
    <style>
        .woo-stats{display:grid;gap:.85rem;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));margin-bottom:1.1rem;}
        .woo-stat{background:#fff;border:1px solid #e7e5e4;border-radius:14px;padding:1rem 1.1rem;}
        .woo-stat p{margin:0;font-size:.75rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;}
        .woo-stat strong{display:block;margin-top:.35rem;font-size:1.35rem;color:#0f172a;}
        .woo-table-wrap{background:#fff;border:1px solid #e7e5e4;border-radius:16px;overflow:hidden;}
        .woo-table{width:100%;min-width:52rem;border-collapse:collapse;text-align:left;font-size:.9rem;}
        .woo-table th{padding:.85rem 1rem;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;color:#78716c;background:#fafaf9;border-bottom:1px solid #e7e5e4;}
        .woo-table td{padding:.95rem 1rem;border-bottom:1px solid #f5f5f4;vertical-align:middle;}
        .woo-table tr:last-child td{border-bottom:0;}
        .woo-table tbody tr:hover{background:#fafaf9;}
        .woo-badge{display:inline-flex;align-items:center;padding:.22rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;text-transform:capitalize;}
        .woo-badge-paid{background:#ecfdf5;color:#065f46;}
        .woo-badge-pending{background:#fff7ed;color:#9a3412;}
        .woo-badge-failed,.woo-badge-cancelled{background:#fef2f2;color:#991b1b;}
        .woo-badge-default{background:#f5f5f4;color:#44403c;}
    </style>

    @php
        $paidCount = $orders->getCollection()->where('payment_status', 'paid')->count();
        $pendingCount = $orders->getCollection()->whereIn('payment_status', ['pending_payment', 'unpaid'])->count();
    @endphp

    <div class="woo-stats">
        <div class="woo-stat">
            <p>Total orders</p>
            <strong>{{ $orders->total() }}</strong>
        </div>
        <div class="woo-stat">
            <p>Paid (this page)</p>
            <strong>{{ $paidCount }}</strong>
        </div>
        <div class="woo-stat">
            <p>Pending (this page)</p>
            <strong>{{ $pendingCount }}</strong>
        </div>
    </div>

    <div class="woo-table-wrap overflow-x-auto">
        <table class="woo-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                @php
                    $badge = match ($order->payment_status) {
                        'paid' => 'woo-badge-paid',
                        'pending_payment', 'unpaid' => 'woo-badge-pending',
                        'failed', 'refunded' => 'woo-badge-failed',
                        default => 'woo-badge-default',
                    };
                @endphp
                <tr>
                    <td>
                        <a class="font-semibold text-teal-800 hover:underline" href="{{ route('tenant.commerce.orders.show', $order) }}">{{ $order->number }}</a>
                        <div class="mt-0.5 text-xs text-slate-400">{{ strtoupper($order->payment_gateway) }}</div>
                    </td>
                    <td>
                        @if ($order->customer)
                            <a class="font-medium text-slate-800 hover:underline" href="{{ route('tenant.commerce.customers.show', $order->customer) }}">{{ $order->customer->name }}</a>
                            <div class="text-xs text-slate-400">{{ $order->customer->email }}</div>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>{{ $order->items->count() }}</td>
                    <td class="font-semibold">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                    <td><span class="woo-badge {{ $badge }}">{{ str_replace('_', ' ', $order->payment_status) }}</span></td>
                    <td class="text-slate-500">{{ $order->created_at->format('M j, Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-10 text-center text-slate-500" colspan="6">No orders yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
@endsection
