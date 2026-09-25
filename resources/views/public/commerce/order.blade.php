@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Order</p>
        <h2>{{ $order->number }}</h2>
    </div>
    <a class="site-btn site-btn-outline" href="{{ route('site.account.orders', ['siteTenant' => $tenant->slug]) }}">All orders</a>
</section>
@if (session('status'))<p>{{ session('status') }}</p>@endif
<p>Payment: <strong>{{ $order->payment_status }}</strong> ({{ strtoupper($order->payment_gateway) }})</p>
<p>Total: <strong>{{ number_format($order->total, 2) }} {{ $order->currency }}</strong></p>
<ul>
@foreach ($order->items as $item)
    <li>{{ $item->title }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2) }}</li>
@endforeach
</ul>
@if ($order->payment_status !== 'paid')
    <a class="site-btn" href="{{ route('site.payment.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">Complete payment</a>
@endif
@endsection
