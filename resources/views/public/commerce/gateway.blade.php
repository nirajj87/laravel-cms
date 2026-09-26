@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Gateway</p>
        <h2>{{ strtoupper($method) }} payment</h2>
    </div>
</section>

<div style="max-width:36rem;background:#fff;border:1px solid var(--site-card-border);border-radius:16px;padding:1.4rem;">
    <p>Order <strong>{{ $order->number }}</strong> · {{ number_format($order->total, 2) }} {{ $order->currency }}</p>
    <p style="color:#64748b;">Demo gateway page. Click below to simulate a successful {{ strtoupper($method) }} payment.</p>
    <form method="POST" action="{{ route('site.payment.gateway.return', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">
        @csrf
        <input type="hidden" name="method" value="{{ $method }}">
        <button class="site-btn" type="submit">Pay {{ number_format($order->total, 2) }} {{ $order->currency }}</button>
    </form>
</div>
@endsection