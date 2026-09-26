<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\InvoiceDocument;
use App\Support\CommerceSettings;
use App\Support\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function show(Tenant $siteTenant, Order $order): View
    {
        $this->authorizeOrderAccess($order);
        $commerce = CommerceSettings::settings($siteTenant);

        return view('public.commerce.payment', [
            'tenant' => $siteTenant,
            'order' => $order->load(['items', 'customer']),
            'commerce' => $commerce,
            'methods' => $this->availableMethods($commerce),
        ]);
    }

    public function confirm(Request $request, Tenant $siteTenant, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);
        $commerce = CommerceSettings::settings($siteTenant);
        $methods = array_keys($this->availableMethods($commerce));

        $data = $request->validate([
            'method' => ['required', 'in:'.implode(',', $methods)],
            'cheque_number' => ['nullable', 'string', 'max:80'],
            'cheque_bank' => ['nullable', 'string', 'max:120'],
            'cheque_date' => ['nullable', 'date'],
            'cash_received_by' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($order->payment_status === 'paid') {
            return redirect()->route('site.payment.thanks', [
                'siteTenant' => $siteTenant->slug,
                'order' => $order->id,
            ]);
        }

        $method = $data['method'];

        if (in_array($method, ['razorpay', 'stripe'], true)) {
            $order->payment_gateway = $method;
            $order->payment_status = 'pending_payment';
            $order->save();

            return redirect()->route('site.payment.gateway', [
                'siteTenant' => $siteTenant->slug,
                'order' => $order->id,
                'method' => $method,
            ]);
        }

        if ($method === 'cheque') {
            $request->validate([
                'cheque_number' => ['required', 'string', 'max:80'],
                'cheque_bank' => ['required', 'string', 'max:120'],
                'cheque_date' => ['required', 'date'],
            ]);
        }

        $meta = is_array($order->billing) ? $order->billing : [];
        $meta['payment'] = [
            'method' => $method,
            'cheque_number' => $data['cheque_number'] ?? null,
            'cheque_bank' => $data['cheque_bank'] ?? null,
            'cheque_date' => $data['cheque_date'] ?? null,
            'cash_received_by' => $data['cash_received_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        $order->billing = $meta;
        $order->payment_gateway = $method;
        $order->payment_status = 'paid';
        $order->status = 'paid';
        $order->paid_at = now();
        $order->notes = $data['notes'] ?? $order->notes;
        $order->save();

        Inventory::decrementForOrder($order->fresh('items'));
        $this->sendInvoice($siteTenant, $order);

        return redirect()->route('site.payment.thanks', [
            'siteTenant' => $siteTenant->slug,
            'order' => $order->id,
        ]);
    }

    public function gateway(Tenant $siteTenant, Order $order, string $method): View
    {
        $this->authorizeOrderAccess($order);
        abort_unless(in_array($method, ['razorpay', 'stripe'], true), 404);

        return view('public.commerce.gateway', [
            'tenant' => $siteTenant,
            'order' => $order->load('items'),
            'method' => $method,
        ]);
    }

    public function gatewayReturn(Request $request, Tenant $siteTenant, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);
        $method = $request->validate(['method' => ['required', 'in:razorpay,stripe']])['method'];

        if ($order->payment_status !== 'paid') {
            $order->payment_gateway = $method;
            $order->payment_status = 'paid';
            $order->status = 'paid';
            $order->paid_at = now();
            $order->save();
            Inventory::decrementForOrder($order->fresh('items'));
            $this->sendInvoice($siteTenant, $order);
        }

        return redirect()->route('site.payment.thanks', [
            'siteTenant' => $siteTenant->slug,
            'order' => $order->id,
        ]);
    }

    public function thanks(Tenant $siteTenant, Order $order): View
    {
        $this->authorizeOrderAccess($order);

        return view('public.commerce.thanks', [
            'tenant' => $siteTenant,
            'order' => $order->load(['items', 'customer']),
            'loginUrl' => route('site.account.login', ['siteTenant' => $siteTenant->slug]),
            'plainPassword' => $order->customer
                ? session('commerce.new_password.'.$order->customer->id)
                : null,
            'invoiceUrl' => route('site.payment.invoice', ['siteTenant' => $siteTenant->slug, 'order' => $order->id]),
        ]);
    }

    public function invoice(Tenant $siteTenant, Order $order, InvoiceDocument $invoices): Response
    {
        $this->authorizeOrderAccess($order);

        return $invoices->download($siteTenant, $order);
    }

    /** @return array<string, string> */
    private function availableMethods(array $commerce): array
    {
        return [
            'cash' => 'Cash',
            'cheque' => 'Cheque / check',
            'razorpay' => 'Razorpay (online)',
            'stripe' => 'Stripe (online)',
        ];
    }

    private function sendInvoice(Tenant $siteTenant, Order $order): void
    {
        $order->loadMissing(['items', 'customer']);
        $customer = $order->customer;
        if (! $customer) {
            return;
        }

        $plain = session('commerce.new_password.'.$customer->id);
        if (! is_string($plain) || $plain === '') {
            $plain = $customer->issued_password;
        }

        try {
            Mail::to($customer->email)->send(new OrderInvoiceMail(
                $siteTenant,
                $order,
                $customer,
                is_string($plain) && $plain !== '' ? $plain : null,
                route('site.account.login', ['siteTenant' => $siteTenant->slug]),
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function authorizeOrderAccess(Order $order): void
    {
        $customer = Auth::guard('customer')->user();
        if ($customer && (int) $order->customer_id === (int) $customer->id) {
            return;
        }

        $accessible = session('commerce.accessible_orders', []);
        abort_unless(in_array((int) $order->id, array_map('intval', (array) $accessible), true), 403);
    }
}