@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Confirmed</p>
        <h2>Thank you</h2>
    </div>
</section>

@php
    $paidAt = $order->paid_at ? $order->paid_at->format('M j, Y H:i') : '—';
    $gstRate = number_format((float) $order->tax_rate, 0);
@endphp

<div style="display:grid;gap:1rem;max-width:28rem;">
    <p style="margin:0;color:#64748b;">Your payment is confirmed for order <strong>{{ $order->number }}</strong>.</p>

    <div style="display:grid;gap:.55rem;">
        <p style="margin:0;display:flex;justify-content:space-between;gap:1rem;"><span>Method</span><strong>{{ strtoupper($order->payment_gateway) }}</strong></p>
        <p style="margin:0;display:flex;justify-content:space-between;gap:1rem;"><span>Status</span><strong>{{ $order->payment_status }}</strong></p>
        <p style="margin:0;display:flex;justify-content:space-between;gap:1rem;"><span>Subtotal</span><strong>{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</strong></p>
        <p style="margin:0;display:flex;justify-content:space-between;gap:1rem;"><span>GST ({{ $gstRate }}%)</span><strong>{{ number_format($order->tax_amount, 2) }} {{ $order->currency }}</strong></p>
        <p style="margin:0;display:flex;justify-content:space-between;gap:1rem;padding-top:.35rem;border-top:1px solid rgba(15,23,42,.1);"><span>Total paid</span><strong>{{ number_format($order->total, 2) }} {{ $order->currency }}</strong></p>
        <p style="margin:0;display:flex;justify-content:space-between;gap:1rem;"><span>Paid at</span><strong>{{ $paidAt }}</strong></p>
    </div>

    <div>
        <p style="margin:0 0 .5rem;font-weight:600;">Items</p>
        <ul style="margin:0;padding-left:1.1rem;">
            @foreach ($order->items as $item)
                <li>{{ $item->title }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2) }}</li>
            @endforeach
        </ul>
    </div>

    <p style="margin:0;color:#64748b;font-size:.92rem;">
        Invoice emailed to <strong>{{ $order->customer?->email }}</strong>
        @if ($plainPassword)
            with your login password
        @endif
        .
    </p>

    <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
        <a class="site-btn" href="{{ $invoiceUrl }}">Download invoice</a>
        <a class="site-btn site-btn-outline" href="{{ $loginUrl }}">Customer login</a>
        <a class="site-btn site-btn-outline" href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">Back to shop</a>
    </div>
</div>
@endsection
