@extends('layouts.app')

@section('title', 'Commerce settings')
@section('kicker', 'Backend Settings')
@section('heading', 'Commerce settings')

@section('content')
    <form method="POST" action="{{ route('tenant.commerce.update') }}" class="card grid max-w-2xl gap-4 p-5">
        @csrf
        @method('PUT')
        <p class="text-sm text-slate-600">Configure cart, checkout, and payment gateways for the public storefront. Customers and orders are under <strong>WooCommerce</strong> in the main menu.</p>
        <input type="hidden" name="cart_enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="cart_enabled" value="1" @checked(old('cart_enabled', $commerce['cart_enabled']))> Enable cart</label>
        <input type="hidden" name="checkout_enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="checkout_enabled" value="1" @checked(old('checkout_enabled', $commerce['checkout_enabled']))> Enable checkout</label>
        <x-field label="Currency" name="currency"><input class="field" name="currency" value="{{ old('currency', $commerce['currency']) }}" maxlength="3"></x-field>
        <x-field label="Payment gateway" name="gateway">
            <select class="field" name="gateway">
                @foreach (['manual' => 'Manual / invoice', 'cod' => 'Cash on delivery', 'razorpay' => 'Razorpay', 'stripe' => 'Stripe'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('gateway', $commerce['gateway']) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Razorpay key" name="razorpay_key"><input class="field" name="razorpay_key" value="{{ old('razorpay_key', $commerce['razorpay_key']) }}"></x-field>
        <x-field label="Razorpay secret" name="razorpay_secret"><input class="field" name="razorpay_secret" value="{{ old('razorpay_secret', $commerce['razorpay_secret']) }}"></x-field>
        <x-field label="Stripe publishable key" name="stripe_key"><input class="field" name="stripe_key" value="{{ old('stripe_key', $commerce['stripe_key']) }}"></x-field>
        <x-field label="Stripe secret" name="stripe_secret"><input class="field" name="stripe_secret" value="{{ old('stripe_secret', $commerce['stripe_secret']) }}"></x-field>
        <button class="btn btn-primary" type="submit">Save commerce</button>
    </form>
@endsection
