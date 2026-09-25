@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Checkout</p>
        <h2>Shipping & billing</h2>
    </div>
</section>

<form method="POST" action="{{ route('site.checkout.store', ['siteTenant' => $tenant->slug]) }}" style="display:grid;gap:1rem;max-width:36rem;">
    @csrf
    <label>Name<input class="site-input" name="name" value="{{ old('name', $customer->name) }}" required></label>
    <label>Email<input class="site-input" type="email" name="email" value="{{ old('email', $customer->email) }}" required></label>
    <label>Phone<input class="site-input" name="phone" value="{{ old('phone', $customer->phone) }}"></label>
    <label>Address<textarea class="site-input" name="address" rows="3">{{ old('address') }}</textarea></label>
    <label>Notes<textarea class="site-input" name="notes" rows="2">{{ old('notes') }}</textarea></label>
    <p style="margin:0;">Total: <strong>{{ number_format($subtotal, 2) }} {{ $commerce['currency'] }}</strong> · Gateway: {{ strtoupper($commerce['gateway']) }}</p>
    <button class="site-btn" type="submit">Place order & pay</button>
</form>
@endsection
