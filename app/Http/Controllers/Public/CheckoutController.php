<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Support\Cart;
use App\Support\CommerceSettings;
use App\Support\CommerceTax;
use App\Support\Inventory;
use App\Support\PlainPassword;
use App\Models\Post;
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

        $customer = Auth::guard('customer')->user();

        return view('public.commerce.checkout', [
            'tenant' => $siteTenant,
            'items' => Cart::items($siteTenant),
            'subtotal' => Cart::subtotal($siteTenant),
            'tax' => CommerceTax::fromSubtotal(Cart::subtotal($siteTenant)),
            'commerce' => CommerceSettings::settings($siteTenant),
            'customer' => $customer,
            'address' => [
                'name' => old('name', $customer->name ?? ''),
                'email' => old('email', $customer->email ?? ''),
                'phone' => old('phone', $customer->phone ?? ''),
                'pincode' => old('pincode', $customer->pincode ?? ''),
                'address' => old('address', $customer->address ?? ''),
                'landmark' => old('landmark', $customer->landmark ?? ''),
            ],
        ]);
    }

    public function store(Request $request, Tenant $siteTenant): RedirectResponse
    {
        abort_unless(CommerceSettings::checkoutEnabled($siteTenant) && CommerceSettings::cartEnabled($siteTenant), 404);
        if (Cart::count($siteTenant) < 1) {
            return redirect()->route('site.cart.show', ['siteTenant' => $siteTenant->slug]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
            'pincode' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = Auth::guard('customer')->user();
        $plainPassword = null;

        if (! $customer) {
            $existing = Customer::query()->where('email', strtolower($data['email']))->first();
            $plainPassword = PlainPassword::alphanumeric(10);
            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'pincode' => $data['pincode'],
                'address' => $data['address'],
                'landmark' => $data['landmark'] ?? null,
                'password' => $plainPassword,
                'issued_password' => $plainPassword,
            ];

            if ($existing) {
                $customer = $existing;
                $customer->fill($payload);
                $customer->save();
            } else {
                $customer = Customer::query()->create([
                    'tenant_id' => $siteTenant->id,
                    'email' => strtolower($data['email']),
                    ...$payload,
                ]);
            }

            Auth::guard('customer')->login($customer);
        } else {
            $customer->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'pincode' => $data['pincode'],
                'address' => $data['address'],
                'landmark' => $data['landmark'] ?? null,
            ]);
            $customer->save();
        }

        if ($plainPassword) {
            $request->session()->put('commerce.new_password.'.$customer->id, $plainPassword);
        }

        $commerce = CommerceSettings::settings($siteTenant);
        $items = Cart::items($siteTenant);
        $subtotal = Cart::subtotal($siteTenant);
        $tax = CommerceTax::fromSubtotal($subtotal);

        foreach ($items as $item) {
            $post = Post::query()->whereKey($item['post_id'])->first();
            if ($post) {
                Inventory::assertAvailable($siteTenant, $post, (int) $item['qty']);
            }
        }

        $shipping = [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'pincode' => $data['pincode'],
            'address' => $data['address'],
            'landmark' => $data['landmark'] ?? '',
        ];

        $order = DB::transaction(function () use ($siteTenant, $customer, $shipping, $items, $tax, $commerce) {
            $order = Order::query()->create([
                'tenant_id' => $siteTenant->id,
                'customer_id' => $customer->id,
                'number' => 'NW-'.strtoupper(substr(uniqid(), -8)),
                'status' => 'pending',
                'payment_status' => 'pending_payment',
                'payment_gateway' => $commerce['gateway'],
                'currency' => $commerce['currency'],
                'subtotal' => $tax['subtotal'],
                'tax_rate' => $tax['tax_rate'],
                'tax_amount' => $tax['tax_amount'],
                'total' => $tax['total'],
                'billing' => $shipping,
                'shipping' => $shipping,
                'notes' => null,
                'paid_at' => null,
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

        $accessible = $request->session()->get('commerce.accessible_orders', []);
        $accessible[] = (int) $order->id;
        $request->session()->put('commerce.accessible_orders', array_values(array_unique($accessible)));

        return redirect()->route('site.payment.show', [
            'siteTenant' => $siteTenant->slug,
            'order' => $order->id,
        ])->with('status', 'Address saved. Choose a payment method.');
    }
}