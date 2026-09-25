@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Account</p>
        <h2>Hello, {{ $customer->name }}</h2>
    </div>
    <form method="POST" action="{{ route('site.account.logout', ['siteTenant' => $tenant->slug]) }}">@csrf<button class="site-btn site-btn-outline" type="submit">Sign out</button></form>
</section>

@if (session('status'))<p>{{ session('status') }}</p>@endif

<div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));margin-bottom:2rem;">
    <a class="site-card" href="{{ route('site.cart.show', ['siteTenant' => $tenant->slug]) }}" style="padding:1.2rem;text-decoration:none;color:inherit;">
        <p class="site-kicker">Cart</p>
        <h2 style="margin:0;">{{ $cartCount }} items</h2>
    </a>
    <a class="site-card" href="{{ route('site.account.orders', ['siteTenant' => $tenant->slug]) }}" style="padding:1.2rem;text-decoration:none;color:inherit;">
        <p class="site-kicker">Orders</p>
        <h2 style="margin:0;">View history</h2>
    </a>
</div>

<h3 style="margin:0 0 1rem;">Recent orders</h3>
@forelse ($orders as $order)
    <p style="margin:0 0 .6rem;"><a href="{{ route('site.account.orders.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">{{ $order->number }}</a> — {{ $order->payment_status }} — {{ number_format($order->total, 2) }} {{ $order->currency }}</p>
@empty
    <p>No orders yet.</p>
@endforelse
@endsection
