@extends('public.layout')

@section('content')
<style>
.chk-panel{display:grid;gap:1.5rem;grid-template-columns:minmax(0,1.2fr) minmax(260px,.75fr);align-items:start;}
.chk-card{background:#fff;border:1px solid var(--site-card-border);border-radius:calc(var(--site-radius) + 4px);box-shadow:var(--site-card-shadow);padding:1.25rem 1.35rem;}
.chk-form{display:grid;gap:1rem;}
.chk-form label{display:grid;gap:.35rem;font-size:.92rem;color:#334155;}
.chk-grid-2{display:grid;gap:1rem;grid-template-columns:1fr 1fr;}
@media (max-width:860px){.chk-panel,.chk-grid-2{grid-template-columns:1fr;}}
</style>

<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Checkout</p>
        <h2>Delivery address</h2>
    </div>
    <a class="site-btn site-btn-outline" href="{{ route('site.cart.show', ['siteTenant' => $tenant->slug]) }}">Back to cart</a>
</section>

@if (session('status'))
    <p style="margin-bottom:1rem;">{{ session('status') }}</p>
@endif

@if ($customer)
    <p style="margin:0 0 1rem;color:#64748b;">Signed in as {{ $customer->email }}. Address fields are filled from your profile — edit if needed.</p>
@else
    <p style="margin:0 0 1rem;color:#64748b;">Enter your address. We will create an account and email a random password to you.</p>
@endif

<div class="chk-panel">
    <form method="POST" action="{{ route('site.checkout.store', ['siteTenant' => $tenant->slug]) }}" class="chk-card chk-form">
        @csrf
        <label>Name
            <input class="site-input" name="name" value="{{ $address['name'] }}" required>
        </label>
        <div class="chk-grid-2">
            <label>Email
                <input class="site-input" type="email" name="email" value="{{ $address['email'] }}" required @readonly($customer)>
            </label>
            <label>Mobile no
                <input class="site-input" name="phone" value="{{ $address['phone'] }}" required>
            </label>
        </div>
        <div class="chk-grid-2">
            <label>Pincode
                <input class="site-input" name="pincode" value="{{ $address['pincode'] }}" required>
            </label>
            <label>Landmark
                <input class="site-input" name="landmark" value="{{ $address['landmark'] }}">
            </label>
        </div>
        <label>Address
            <textarea class="site-input" name="address" rows="3" required>{{ $address['address'] }}</textarea>
        </label>
        <p style="margin:0;color:#64748b;font-size:.92rem;">
            Subtotal {{ number_format($tax['subtotal'], 2) }} + GST {{ number_format($tax['tax_rate'], 0) }}% ({{ number_format($tax['tax_amount'], 2) }}) =
            <strong style="color:var(--site-text);">{{ number_format($tax['total'], 2) }} {{ $commerce['currency'] }}</strong>
        </p>
        <button class="site-btn" type="submit" style="justify-content:center;width:100%;padding:.85rem 1.2rem;font-size:1rem;">Pay</button>
    </form>

    <aside class="chk-card">
        <h3 style="margin:0 0 .85rem;">Order summary</h3>
        <ul style="margin:0;padding:0;list-style:none;">
            @foreach ($items as $item)
                <li style="display:flex;justify-content:space-between;gap:1rem;padding:.45rem 0;border-bottom:1px solid rgba(15,23,42,.06);">
                    <span>{{ $item['title'] }} × {{ $item['qty'] }}</span>
                    <span>{{ number_format($item['price'] * $item['qty'], 2) }}</span>
                </li>
            @endforeach
        </ul>
        <p style="display:flex;justify-content:space-between;margin:1rem 0 0;font-weight:700;">
            <span>Total</span>
            <span>{{ number_format($subtotal, 2) }} {{ $commerce['currency'] }}</span>
        </p>
    </aside>
</div>
@endsection