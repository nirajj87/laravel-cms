@extends('public.layout')

@section('content')
<style>
.shop-panel{display:grid;gap:1.5rem;grid-template-columns:minmax(0,1.4fr) minmax(260px,.7fr);align-items:start;margin-top:.5rem;}
.shop-card{background:#fff;border:1px solid var(--site-card-border);border-radius:calc(var(--site-radius) + 4px);box-shadow:var(--site-card-shadow);padding:1.15rem 1.25rem;}
.shop-line{display:flex;gap:1rem;align-items:center;padding:1rem 0;border-bottom:1px solid rgba(15,23,42,.08);}
.shop-line:last-child{border-bottom:0;padding-bottom:0;}
.shop-line:first-child{padding-top:0;}
.shop-actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;}
.shop-side h3{margin:0 0 .75rem;font-size:1.05rem;}
.shop-login label{display:grid;gap:.35rem;margin-bottom:.75rem;font-size:.92rem;}
@media (max-width:860px){.shop-panel{grid-template-columns:1fr;}}
</style>

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
@if ($errors->any())
    <p style="margin-bottom:1rem;color:#b91c1c;">{{ $errors->first() }}</p>
@endif

@if (count($items) === 0)
    <div class="shop-card"><p style="margin:0;">Your cart is empty.</p></div>
@else
<div class="shop-panel">
    <div class="shop-card">
        @foreach ($items as $item)
            <div class="shop-line">
                <div style="flex:1;min-width:0;">
                    <h3 style="margin:0;font-size:1.05rem;">{{ $item['title'] }}</h3>
                    <p class="site-meta" style="margin:.35rem 0 0;">{{ number_format($item['price'], 2) }} {{ $commerce['currency'] }} each</p>
                </div>
                <div class="shop-actions">
                    <form method="POST" action="{{ route('site.cart.update', ['siteTenant' => $tenant->slug]) }}" class="shop-actions">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="post_id" value="{{ $item['post_id'] }}">
                        <input class="site-input" style="width:4.2rem;padding:.45rem;" type="number" name="qty" min="0" max="99" value="{{ $item['qty'] }}">
                        <button class="site-btn" type="submit">Update</button>
                    </form>
                    <form method="POST" action="{{ route('site.cart.remove', ['siteTenant' => $tenant->slug]) }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="post_id" value="{{ $item['post_id'] }}">
                        <button class="site-btn site-btn-outline" type="submit">Remove</button>
                    </form>
                </div>
            </div>
        @endforeach
        <div style="display:grid;gap:.35rem;margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(15,23,42,.08);">
            <p style="margin:0;display:flex;justify-content:space-between;"><span>Subtotal</span><span>{{ number_format($tax['subtotal'], 2) }} {{ $commerce['currency'] }}</span></p>
            <p style="margin:0;display:flex;justify-content:space-between;"><span>GST ({{ number_format($tax['tax_rate'], 0) }}%)</span><span>{{ number_format($tax['tax_amount'], 2) }} {{ $commerce['currency'] }}</span></p>
            <p style="margin:0;display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;"><span>Total</span><span>{{ number_format($tax['total'], 2) }} {{ $commerce['currency'] }}</span></p>
        </div>
    </div>

    <aside class="shop-card shop-side">
        @if ($customer)
            <h3>Signed in</h3>
            <p style="margin:0 0 1rem;color:#64748b;">{{ $customer->name }} · {{ $customer->email }}</p>
            @if ($commerce['checkout_enabled'])
                <a class="site-btn" style="width:100%;justify-content:center;" href="{{ route('site.checkout.show', ['siteTenant' => $tenant->slug]) }}">Proceed to checkout</a>
            @endif
        @else
            <h3>Have an account?</h3>
            <p style="margin:0 0 1rem;color:#64748b;font-size:.92rem;">Login to autofill your saved address on checkout.</p>
            <form method="POST" action="{{ route('site.account.login.store', ['siteTenant' => $tenant->slug]) }}" class="shop-login">
                @csrf
                <input type="hidden" name="redirect" value="{{ route('site.checkout.show', ['siteTenant' => $tenant->slug]) }}">
                <label>Email<input class="site-input" type="email" name="email" value="{{ old('email') }}" required></label>
                <label>Password<input class="site-input" type="password" name="password" required></label>
                <button class="site-btn" style="width:100%;justify-content:center;" type="submit">Login & checkout</button>
            </form>
            <p style="margin:1rem 0;text-align:center;color:#94a3b8;font-size:.85rem;">or</p>
            @if ($commerce['checkout_enabled'])
                <a class="site-btn site-btn-outline" style="width:100%;justify-content:center;" href="{{ route('site.checkout.show', ['siteTenant' => $tenant->slug]) }}">Checkout as guest</a>
                <p style="margin:.75rem 0 0;color:#64748b;font-size:.82rem;">Guest checkout creates an account and emails a password.</p>
            @endif
        @endif
    </aside>
</div>
@endif
@endsection