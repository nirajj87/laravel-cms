@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Payment</p>
        <h2>Order {{ $order->number }}</h2>
    </div>
</section>

<div style="max-width:36rem;display:grid;gap:1rem;">
    <p style="margin:0;">Status: <strong>{{ $order->payment_status }}</strong> via {{ strtoupper($order->payment_gateway) }}</p>
    <p style="margin:0;">Total: <strong>{{ number_format($order->total, 2) }} {{ $order->currency }}</strong></p>
    <ul style="margin:0;padding-left:1.1rem;">
        @foreach ($order->items as $item)
            <li>{{ $item->title }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2) }}</li>
        @endforeach
    </ul>

    @if ($order->payment_status === 'paid')
        <p>Payment received. Thank you!</p>
        <a class="site-btn" href="{{ route('site.account.orders.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">View order</a>
    @else
        <p style="margin:0;color:#64748b;">Confirm payment with the configured {{ strtoupper($order->payment_gateway) }} gateway (demo mode).</p>
        <form method="POST" action="{{ route('site.payment.confirm', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">
            @csrf
            <button class="site-btn" type="submit">Confirm payment</button>
        </form>
    @endif
</div>
@endsection
