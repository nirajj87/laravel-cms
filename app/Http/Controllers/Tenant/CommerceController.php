<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use App\Support\CommerceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommerceController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);

        return view('tenant.commerce.edit', [
            'commerce' => CommerceSettings::settings(current_tenant()),
            'orders' => Order::query()->with('customer')->latest()->limit(25)->get(),
        ]);
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('commerce.update'), 403);
        $tenant = current_tenant();
        $draft = new Tenant;
        $draft->settings = ['commerce' => $request->all()];
        $settings = $tenant->settings ?? [];
        $settings['commerce'] = CommerceSettings::settings($draft);
        $tenant->settings = $settings;
        $tenant->save();
        $activity->log('commerce.updated', 'Updated commerce settings', $tenant, [
            'gateway' => $settings['commerce']['gateway'],
        ], $tenant->id);

        return back()->with('status', 'Commerce settings saved.');
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('commerce.update'), 403);
        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,cancelled,fulfilled'],
            'payment_status' => ['required', 'in:unpaid,pending_payment,paid,failed,refunded'],
        ]);
        $order->fill($data);
        if ($data['payment_status'] === 'paid' && ! $order->paid_at) {
            $order->paid_at = now();
        }
        $order->save();

        return back()->with('status', 'Order updated.');
    }
}
