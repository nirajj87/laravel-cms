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
        $orders = Order::query()->where('customer_id', $customer->id)->latest()->limit(5)->get();

        return view('public.commerce.dashboard', [
            'tenant' => $siteTenant,
            'customer' => $customer,
            'orders' => $orders,
            'cartCount' => Cart::count($siteTenant),
            'commerce' => CommerceSettings::settings($siteTenant),
        ]);
    }

    public function orders(Tenant $siteTenant): View
    {
        $customer = Auth::guard('customer')->user();
        $orders = Order::query()->where('customer_id', $customer->id)->withCount('items')->latest()->paginate(12);

        return view('public.commerce.orders', [
            'tenant' => $siteTenant,
            'customer' => $customer,
            'orders' => $orders,
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
        ]);
    }
}
