<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use App\Support\CommerceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function show(Tenant $siteTenant, Order $order): View
    {
        $this->authorizeOrder($order);
        $commerce = CommerceSettings::settings($siteTenant);

        return view('public.commerce.payment', [
            'tenant' => $siteTenant,
            'order' => $order->load('items'),
            'commerce' => $commerce,
        ]);
    }

    public function confirm(Request $request, Tenant $siteTenant, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);
        if ($order->payment_status === 'paid') {
            return redirect()->route('site.account.orders.show', [
                'siteTenant' => $siteTenant->slug,
                'order' => $order->id,
            ])->with('status', 'Order already paid.');
        }

        $gateway = $order->payment_gateway;
        // Demo confirm for razorpay/stripe (keys configured) or force-pay for manual/cod already paid
        if (in_array($gateway, ['razorpay', 'stripe'], true)) {
            $commerce = CommerceSettings::settings($siteTenant);
            $hasKeys = $gateway === 'razorpay'
                ? ($commerce['razorpay_key'] !== '' && $commerce['razorpay_secret'] !== '')
                : ($commerce['stripe_key'] !== '' && $commerce['stripe_secret'] !== '');
            abort_unless($hasKeys, 422, 'Payment gateway is not configured.');
        }

        $order->payment_status = 'paid';
        $order->status = 'paid';
        $order->paid_at = now();
        $order->save();

        return redirect()->route('site.account.orders.show', [
            'siteTenant' => $siteTenant->slug,
            'order' => $order->id,
        ])->with('status', 'Payment confirmed.');
    }

    private function authorizeOrder(Order $order): void
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer && (int) $order->customer_id === (int) $customer->id, 403);
    }
}
