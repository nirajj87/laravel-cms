@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Shop</p>
        <h2>Your cart</h2>
    </div>
    <a class="site-btn site-btn-outline" href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">Continue shopping</a>
</section>

@if (session('status'))
    <p style="margin-bottom:1rem;">{{ session('status') }}</p>
@endif

@if (count($items) === 0)
    <p>Your cart is empty.</p>
@else
    <div style="display:grid;gap:1rem;max-width:48rem;">
        @foreach ($items as $item)
            <article class="site-card" style="flex-direction:row;align-items:center;padding:1rem 1.2rem;gap:1rem;">
                <div style="flex:1;">
                    <h2 style="font-size:1.05rem;margin:0;">{{ $item['title'] }}</h2>
                    <p class="site-meta" style="margin-top:.35rem;">{{ number_format($item['price'], 2) }} {{ $commerce['currency'] }} × {{ $item['qty'] }}</p>
                </div>
                <form method="POST" action="{{ route('site.cart.update', ['siteTenant' => $tenant->slug]) }}" style="display:flex;gap:.5rem;align-items:center;">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="post_id" value="{{ $item['post_id'] }}">
                    <input class="site-input" style="width:4.5rem;padding:.45rem;" type="number" name="qty" min="0" max="99" value="{{ $item['qty'] }}">
                    <button class="site-btn" type="submit">Update</button>
                </form>
                <form method="POST" action="{{ route('site.cart.remove', ['siteTenant' => $tenant->slug]) }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="post_id" value="{{ $item['post_id'] }}">
                    <button class="site-btn site-btn-outline" type="submit">Remove</button>
                </form>
            </article>
        @endforeach
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:.5rem;">
            <p style="margin:0;font-size:1.1rem;font-weight:600;">Subtotal: {{ number_format($subtotal, 2) }} {{ $commerce['currency'] }}</p>
            @if ($commerce['checkout_enabled'])
                <a class="site-btn" href="{{ route('site.checkout.show', ['siteTenant' => $tenant->slug]) }}">Checkout</a>
            @endif
        </div>
    </div>
@endif
@endsection
