@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Orders</p>
        <h2>Your orders</h2>
    </div>
    <a class="site-btn site-btn-outline" href="{{ route('site.account.dashboard', ['siteTenant' => $tenant->slug]) }}">Dashboard</a>
</section>
@forelse ($orders as $order)
    <article class="site-card" style="padding:1rem 1.2rem;margin-bottom:.8rem;">
        <h2 style="margin:0;font-size:1.05rem;"><a href="{{ route('site.account.orders.show', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">{{ $order->number }}</a></h2>
        <p class="site-meta">{{ $order->created_at->format('M j, Y') }} · {{ $order->items_count }} items · {{ $order->payment_status }} · {{ number_format($order->total, 2) }} {{ $order->currency }}</p>
    </article>
@empty
    <p>No orders yet.</p>
@endforelse
{{ $orders->links() }}
@endsection
