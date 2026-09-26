@extends('public.layout')

@section('content')
<style>
.thanks-card{max-width:42rem;background:#fff;border:1px solid var(--site-card-border);border-radius:18px;box-shadow:var(--site-card-shadow);padding:1.5rem 1.6rem;}
</style>

<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Confirmed</p>
        <h2>Thank you</h2>
    </div>
</section>

<div class="thanks-card">
    <p style="margin:0 0 .75rem;font-size:1.05rem;">Your payment is confirmed.</p>
    <p style="margin:0 0 .35rem;"><strong>Order:</strong> {{ $order->number }}</p>
    <p style="margin:0 0 .35rem;"><strong>Payment method:</strong> {{ strtoupper($order->payment_gateway) }}</p>
    <p style="margin:0 0 .35rem;"><strong>Status:</strong> {{ $order->payment_status }}</p>
    <p style="margin:0 0 .35rem;"><strong>Subtotal:</strong> {{ number_format($order->subtotal, 2) }} {{ $order->currency }}</p>
    <p style="margin:0 0 .35rem;"><strong>GST ({{ number_format((float) $order->tax_rate, 0) }}%):</strong> {{ number_format($order->tax_amount, 2) }} {{ $order->currency }}</p>
    <p style="margin:0 0 .35rem;"><strong>Total paid:</strong> {{ number_format($order->total, 2) }} {{ $order->currency }}</p>
    <p style="margin:0 0 1rem;"><strong>Paid at:</strong> {{ $order->paid_at?->format('M j, Y H:i') ?? '—' }}</p>

    <h3 style="margin:0 0 .5rem;font-size:1rem;">Items</h3>
    <ul style="margin:0 0 1rem;padding-left:1.1rem;">
        @foreach ($order->items as $item)
            <li>{{ $item->title }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2) }}</li>
        @endforeach
    </ul>

    <p style="margin:0 0 1rem;color:#64748b;">An invoice email with tax invoice attached was sent to <strong>{{ $order->customer?->email }}</strong>@if($plainPassword) with your login password@endif.</p>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <a class="site-btn" href="{{ $invoiceUrl }}">Download invoice</a>
        <a class="site-btn site-btn-outline" href="{{ $loginUrl }}">Customer login</a>
        <a class="site-btn site-btn-outline" href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">Back to shop</a>
    </div>
</div>
@endsection
