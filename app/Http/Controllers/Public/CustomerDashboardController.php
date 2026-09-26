<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use App\Support\Cart;
use App\Support\CommerceSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function dashboard(Tenant $siteTenant): View
    {
        $customer = Auth::guard('customer')->user();
        $base = Order::query()->where('customer_id', $customer->id);

        return view('public.commerce.dashboard', [
            'tenant' => $siteTenant,
            'customer' => $customer,
            'orders' => (clone $base)->withCount('items')->latest()->limit(8)->get(),
            'cartCount' => Cart::count($siteTenant),
            'commerce' => CommerceSettings::settings($siteTenant),
            'stats' => [
                'orders' => (clone $base)->count(),
                'paid' => (clone $base)->where('payment_status', 'paid')->count(),
                'pending' => (clone $base)->whereIn('payment_status', ['pending_payment', 'unpaid'])->count(),
                'spent' => (float) (clone $base)->where('payment_status', 'paid')->sum('total'),
            ],
        ]);
    }

    public function orders(Tenant $siteTenant): View
    {
        $customer = Auth::guard('customer')->user();
        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->withCount('items')
            ->latest()
            ->paginate(12);

        return view('public.commerce.orders', [
            'tenant' => $siteTenant,
            'customer' => $customer,
            'orders' => $orders,
            'cartCount' => Cart::count($siteTenant),
            'commerce' => CommerceSettings::settings($siteTenant),
        ]);
    }

    public function order(Tenant $siteTenant, Order $order): View
    {
        $customer = Auth::guard('customer')->user();
        abort_unless((int) $order->customer_id === (int) $customer->id, 403);

        return view('public.commerce.order', [
            'tenant' => $siteTenant,
            'customer' => $customer,
            'order' => $order->load('items'),
            'cartCount' => Cart::count($siteTenant),
            'commerce' => CommerceSettings::settings($siteTenant),
        ]);
    }
}
