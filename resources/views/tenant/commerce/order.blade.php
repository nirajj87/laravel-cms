@extends('layouts.app')
@section('title', $order->number)
@section('kicker', 'WooCommerce · Orders')
@section('heading', $order->number)

@section('content')
    <style>
        .woo-panel{background:#fff;border:1px solid #e7e5e4;border-radius:16px;padding:1.25rem 1.35rem;}
        .woo-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));}
        .woo-label{margin:0;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;}
        .woo-value{margin:.3rem 0 0;font-size:.95rem;color:#0f172a;}
        .woo-badge{display:inline-flex;padding:.22rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;text-transform:capitalize;}
        .woo-badge-paid{background:#ecfdf5;color:#065f46;}
        .woo-badge-pending{background:#fff7ed;color:#9a3412;}
        .woo-badge-failed{background:#fef2f2;color:#991b1b;}
        .woo-badge-default{background:#f5f5f4;color:#44403c;}
        .woo-items{width:100%;border-collapse:collapse;font-size:.9rem;}
        .woo-items th{padding:.7rem 0;text-align:left;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;border-bottom:1px solid #e7e5e4;}
        .woo-items td{padding:.85rem 0;border-bottom:1px solid #f5f5f4;}
        .woo-items tr:last-child td{border-bottom:0;}
    </style>

    @php
        $badge = match ($order->payment_status) {
            'paid' => 'woo-badge-paid',
            'pending_payment', 'unpaid' => 'woo-badge-pending',
            'failed', 'refunded' => 'woo-badge-failed',
            default => 'woo-badge-default',
        };
    @endphp

    <div class="mb-4 flex flex-wrap gap-2">
        <a class="btn btn-secondary" href="{{ route('tenant.commerce.orders') }}">All orders</a>
        @if ($order->customer)
            <a class="btn btn-secondary" href="{{ route('tenant.commerce.customers.show', $order->customer) }}">Customer</a>
        @endif
        <a class="btn btn-primary" href="{{ route('tenant.commerce.orders.invoice', $order) }}">Download invoice</a>
    </div>

    <div class="woo-panel mb-5">
        <div class="woo-grid">
            <div>
                <p class="woo-label">Status</p>
                <p class="woo-value capitalize">{{ $order->status }}</p>
            </div>
            <div>
                <p class="woo-label">Payment</p>
                <p class="woo-value"><span class="woo-badge {{ $badge }}">{{ str_replace('_', ' ', $order->payment_status) }}</span></p>
            </div>
            <div>
                <p class="woo-label">Gateway</p>
                <p class="woo-value uppercase">{{ $order->payment_gateway }}</p>
            </div>
            <div>
                <p class="woo-label">Total</p>
                <p class="woo-value font-semibold">{{ number_format($order->total, 2) }} {{ $order->currency }}</p>
            </div>
            <div>
                <p class="woo-label">Subtotal</p>
                <p class="woo-value">{{ number_format($order->subtotal, 2) }}</p>
            </div>
            <div>
                <p class="woo-label">GST ({{ number_format((float) $order->tax_rate, 0) }}%)</p>
                <p class="woo-value">{{ number_format($order->tax_amount, 2) }}</p>
            </div>
            <div>
                <p class="woo-label">Date</p>
                <p class="woo-value">{{ $order->created_at->format('M j, Y H:i') }}</p>
            </div>
            @if ($order->paid_at)
                <div>
                    <p class="woo-label">Paid at</p>
                    <p class="woo-value">{{ $order->paid_at->format('M j, Y H:i') }}</p>
                </div>
            @endif
            @if ($order->customer)
                <div style="grid-column:1/-1;">
                    <p class="woo-label">Customer</p>
                    <p class="woo-value">
                        <a class="text-teal-800 hover:underline" href="{{ route('tenant.commerce.customers.show', $order->customer) }}">{{ $order->customer->name }}</a>
                        <span class="text-slate-500"> · {{ $order->customer->email }}</span>
                    </p>
                </div>
            @endif
            @if (is_array($order->shipping))
                <div style="grid-column:1/-1;">
                    <p class="woo-label">Shipping</p>
                    <p class="woo-value">
                        {{ $order->shipping['name'] ?? '' }} ·
                        {{ $order->shipping['address'] ?? '' }} ·
                        {{ $order->shipping['pincode'] ?? '' }}
                        @if (!empty($order->shipping['phone'])) · {{ $order->shipping['phone'] }} @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="woo-panel">
        <h2 class="mb-3 mt-0 text-base font-semibold text-slate-900">Items</h2>
        <table class="woo-items">
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
                        <td class="font-medium">{{ number_format($item->line_total, 2) }} {{ $order->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @permission('commerce.update')
        <form method="POST" action="{{ route('tenant.commerce.orders.update', $order) }}" class="woo-panel mt-5 grid max-w-xl gap-3">
            @csrf
            @method('PUT')
            <h2 class="m-0 text-base font-semibold text-slate-900">Update order</h2>
            <x-field label="Status" name="status">
                <select class="field" name="status">
                    @foreach (['pending', 'paid', 'fulfilled', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="Payment status" name="payment_status">
                <select class="field" name="payment_status">
                    @foreach (['unpaid', 'pending_payment', 'paid', 'failed', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(old('payment_status', $order->payment_status) === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </x-field>
            <button class="btn btn-primary" type="submit">Save order</button>
        </form>
    @endpermission
@endsection
