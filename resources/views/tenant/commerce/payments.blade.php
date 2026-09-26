@extends('layouts.app')
@section('title', 'Payments')
@section('kicker', 'WooCommerce')
@section('heading', 'Payment history')

@section('content')
<style>
.pay-stats{display:grid;gap:.85rem;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:1.1rem;}
.pay-stat{background:#fff;border:1px solid #e7e5e4;border-radius:14px;padding:1rem 1.1rem;}
.pay-stat p{margin:0;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;}
.pay-stat strong{display:block;margin-top:.35rem;font-size:1.25rem;}
.pay-wrap{background:#fff;border:1px solid #e7e5e4;border-radius:16px;overflow:hidden;}
.pay-table{width:100%;min-width:58rem;border-collapse:collapse;font-size:.9rem;}
.pay-table th{padding:.85rem 1rem;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;background:#fafaf9;border-bottom:1px solid #e7e5e4;text-align:left;}
.pay-table td{padding:.95rem 1rem;border-bottom:1px solid #f5f5f4;vertical-align:top;}
.pay-badge{display:inline-flex;padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;text-transform:capitalize;}
.pay-paid{background:#ecfdf5;color:#065f46;}
.pay-pending{background:#fff7ed;color:#9a3412;}
.pay-failed{background:#fef2f2;color:#991b1b;}
</style>

<div class="pay-stats">
    <div class="pay-stat"><p>Paid revenue</p><strong>{{ number_format($stats['paid_total'], 2) }} {{ $commerce['currency'] }}</strong></div>
    <div class="pay-stat"><p>GST collected</p><strong>{{ number_format($stats['tax_total'], 2) }}</strong></div>
    <div class="pay-stat"><p>Pending amount</p><strong>{{ number_format($stats['pending_total'], 2) }}</strong></div>
    <div class="pay-stat"><p>Paid count</p><strong>{{ $stats['count'] }}</strong></div>
</div>

<div class="pay-wrap overflow-x-auto">
    <table class="pay-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Method</th>
                <th>Breakdown</th>
                <th>Status</th>
                <th>Paid at</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($payments as $order)
            @php
                $badge = match ($order->payment_status) {
                    'paid' => 'pay-paid',
                    'pending_payment', 'unpaid' => 'pay-pending',
                    default => 'pay-failed',
                };
                $paymentMeta = is_array($order->billing['payment'] ?? null) ? $order->billing['payment'] : [];
            @endphp
            <tr>
                <td>
                    <a class="font-semibold text-teal-800 hover:underline" href="{{ route('tenant.commerce.orders.show', $order) }}">{{ $order->number }}</a>
                    <div class="text-xs text-slate-400">{{ $order->items->count() }} items</div>
                </td>
                <td>
                    @if ($order->customer)
                        <a class="hover:underline" href="{{ route('tenant.commerce.customers.show', $order->customer) }}">{{ $order->customer->name }}</a>
                        <div class="text-xs text-slate-400">{{ $order->customer->email }}</div>
                    @else — @endif
                </td>
                <td>
                    <div class="font-medium uppercase">{{ $order->payment_gateway }}</div>
                    @if (!empty($paymentMeta['cheque_number']))
                        <div class="text-xs text-slate-500">Cheque {{ $paymentMeta['cheque_number'] }} · {{ $paymentMeta['cheque_bank'] ?? '' }}</div>
                    @endif
                    @if (!empty($paymentMeta['cash_received_by']))
                        <div class="text-xs text-slate-500">Cash by {{ $paymentMeta['cash_received_by'] }}</div>
                    @endif
                    @if (!empty($paymentMeta['notes']))
                        <div class="text-xs text-slate-500">{{ $paymentMeta['notes'] }}</div>
                    @endif
                </td>
                <td class="text-sm">
                    <div>Subtotal {{ number_format($order->subtotal, 2) }}</div>
                    <div>GST {{ number_format((float) $order->tax_rate, 0) }}% · {{ number_format($order->tax_amount, 2) }}</div>
                    <div class="font-semibold">Total {{ number_format($order->total, 2) }} {{ $order->currency }}</div>
                </td>
                <td><span class="pay-badge {{ $badge }}">{{ str_replace('_', ' ', $order->payment_status) }}</span></td>
                <td class="text-slate-500">{{ $order->paid_at?->format('M j, Y H:i') ?? '—' }}</td>
                <td class="whitespace-nowrap">
                    <a class="btn btn-secondary" href="{{ route('tenant.commerce.orders.invoice', $order) }}">Invoice</a>
                </td>
            </tr>
        @empty
            <tr><td class="px-4 py-10 text-center text-slate-500" colspan="7">No payment history yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($payments->hasPages())
    <div class="mt-4">{{ $payments->links() }}</div>
@endif
@endsection
