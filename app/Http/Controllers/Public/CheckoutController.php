<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Support\Cart;
use App\Support\CommerceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Tenant $siteTenant): View|RedirectResponse
    {
        abort_unless(CommerceSettings::checkoutEnabled($siteTenant) && CommerceSettings::cartEnabled($siteTenant), 404);
        if (Cart::count($siteTenant) < 1) {
            return redirect()->route('site.cart.show', ['siteTenant' => $siteTenant->slug])
                ->with('status', 'Your cart is empty.');
        }
        if (! Auth::guard('customer')->check()) {
            return redirect()->route('site.account.login', ['siteTenant' => $siteTenant->slug])
                ->with('status', 'Sign in to checkout.');
        }

        return view('public.commerce.checkout', [
            'tenant' => $siteTenant,
            'items' => Cart::items($siteTenant),
            'subtotal' => Cart::subtotal($siteTenant),
            'commerce' => CommerceSettings::settings($siteTenant),
            'customer' => Auth::guard('customer')->user(),
        ]);
    }

    public function store(Request $request, Tenant $siteTenant): RedirectResponse
    {
        abort_unless(CommerceSettings::checkoutEnabled($siteTenant) && CommerceSettings::cartEnabled($siteTenant), 404);
        abort_unless(Auth::guard('customer')->check(), 403);
        if (Cart::count($siteTenant) < 1) {
            return redirect()->route('site.cart.show', ['siteTenant' => $siteTenant->slug]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:400'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $commerce = CommerceSettings::settings($siteTenant);
        $customer = Auth::guard('customer')->user();
        $items = Cart::items($siteTenant);
        $subtotal = Cart::subtotal($siteTenant);
        $gateway = $commerce['gateway'];
        $paymentStatus = in_array($gateway, ['manual', 'cod'], true) ? 'paid' : 'pending_payment';
        $status = $paymentStatus === 'paid' ? 'paid' : 'pending';

        $order = DB::transaction(function () use ($siteTenant, $customer, $data, $items, $subtotal, $commerce, $gateway, $paymentStatus, $status) {
            $order = Order::query()->create([
                'tenant_id' => $siteTenant->id,
                'customer_id' => $customer->id,
                'number' => 'NW-'.strtoupper(substr(uniqid(), -8)),
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_gateway' => $gateway,
                'currency' => $commerce['currency'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'billing' => [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? '',
                    'address' => $data['address'] ?? '',
                ],
                'shipping' => [
                    'address' => $data['address'] ?? '',
                ],
                'notes' => $data['notes'] ?? null,
                'paid_at' => $paymentStatus === 'paid' ? now() : null,
            ]);

            foreach ($items as $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'post_id' => $item['post_id'],
                    'title' => $item['title'],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'line_total' => round($item['price'] * $item['qty'], 2),
                ]);
            }

            return $order;
        });

        Cart::clear($siteTenant);

        return redirect()->route('site.payment.show', [
            'siteTenant' => $siteTenant->slug,
            'order' => $order->id,
        ]);
    }
}
