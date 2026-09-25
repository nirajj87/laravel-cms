@extends('layouts.app')

@section('title', 'Commerce')
@section('kicker', 'Backend Settings')
@section('heading', 'Commerce')

@section('content')
    <form method="POST" action="{{ route('tenant.commerce.update') }}" class="card grid max-w-2xl gap-4 p-5">
        @csrf
        @method('PUT')
        <p class="text-sm text-slate-600">Cart, checkout, and payment gateway for the public site. Razorpay/Stripe run in demo confirm mode when keys are set.</p>
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

    <div class="card mt-6 overflow-x-auto p-5">
        <h2 class="mb-3 text-lg font-semibold text-slate-900">Recent orders</h2>
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead class="text-slate-500">
                <tr>
                    <th class="py-2 pr-3">Number</th>
                    <th class="py-2 pr-3">Customer</th>
                    <th class="py-2 pr-3">Total</th>
                    <th class="py-2 pr-3">Payment</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2">Update</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-t border-stone-200">
                        <td class="py-2 pr-3 font-medium">{{ $order->number }}</td>
                        <td class="py-2 pr-3">{{ $order->customer?->email ?? '—' }}</td>
                        <td class="py-2 pr-3">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                        <td class="py-2 pr-3">{{ $order->payment_status }} / {{ $order->payment_gateway }}</td>
                        <td class="py-2 pr-3">{{ $order->status }}</td>
                        <td class="py-2">
                            <form method="POST" action="{{ route('tenant.commerce.orders.update', $order) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PUT')
                                <select class="field py-1" name="status">
                                    @foreach (['pending','paid','fulfilled','cancelled'] as $status)
                                        <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                                <select class="field py-1" name="payment_status">
                                    @foreach (['unpaid','pending_payment','paid','failed','refunded'] as $status)
                                        <option value="{{ $status }}" @selected($order->payment_status === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary py-1" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-slate-500" colspan="6">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
