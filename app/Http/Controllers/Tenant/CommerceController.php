<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\FieldType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Post;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use App\Services\InvoiceDocument;
use App\Support\CommerceSettings;
use App\Support\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommerceController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);

        return view('tenant.commerce.edit', [
            'commerce' => CommerceSettings::settings(current_tenant()),
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
        $wasPaid = $order->payment_status === 'paid';
        $order->fill($data);
        if ($data['payment_status'] === 'paid' && ! $order->paid_at) {
            $order->paid_at = now();
        }
        $order->save();

        if (! $wasPaid && $order->payment_status === 'paid') {
            Inventory::decrementForOrder($order->fresh('items'));
        }

        return back()->with('status', 'Order updated.');
    }

    public function customers(): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);

        return view('tenant.commerce.customers', [
            'customers' => Customer::query()->withCount('orders')->latest()->paginate(30),
            'commerce' => CommerceSettings::settings(current_tenant()),
        ]);
    }

    public function customer(Customer $customer): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);
        $customer->load(['orders' => fn ($q) => $q->with('items')->latest()]);

        return view('tenant.commerce.customer', [
            'customer' => $customer,
            'commerce' => CommerceSettings::settings(current_tenant()),
        ]);
    }

    public function orders(): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);

        return view('tenant.commerce.orders', [
            'orders' => Order::query()->with(['customer', 'items'])->latest()->paginate(30),
            'commerce' => CommerceSettings::settings(current_tenant()),
        ]);
    }

    public function order(Order $order): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);

        return view('tenant.commerce.order', [
            'order' => $order->load(['customer', 'items']),
            'commerce' => CommerceSettings::settings(current_tenant()),
        ]);
    }

    public function invoice(Order $order, InvoiceDocument $invoices): Response
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);

        return $invoices->download(current_tenant(), $order->load(['items', 'customer']));
    }

    public function payments(): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);

        $payments = Order::query()
            ->with(['customer', 'items'])
            ->whereIn('payment_status', ['paid', 'pending_payment', 'failed', 'refunded'])
            ->latest('paid_at')
            ->latest()
            ->paginate(30);

        return view('tenant.commerce.payments', [
            'payments' => $payments,
            'commerce' => CommerceSettings::settings(current_tenant()),
            'stats' => [
                'paid_total' => (float) Order::query()->where('payment_status', 'paid')->sum('total'),
                'tax_total' => (float) Order::query()->where('payment_status', 'paid')->sum('tax_amount'),
                'pending_total' => (float) Order::query()->where('payment_status', 'pending_payment')->sum('total'),
                'count' => Order::query()->where('payment_status', 'paid')->count(),
            ],
        ]);
    }

    public function inventory(): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);
        $tenant = current_tenant();

        $pricedPosts = Post::query()
            ->with(['contentType.fields', 'inventoryItem'])
            ->latest()
            ->limit(200)
            ->get()
            ->filter(function (Post $post) {
                $type = $post->contentType;
                if (! $type) {
                    return false;
                }

                return $type->fields->contains(
                    fn ($field) => $field->enabled && in_array($field->type, [FieldType::Price, FieldType::Currency], true)
                );
            });

        foreach ($pricedPosts as $post) {
            Inventory::forPost($tenant, $post);
        }

        $items = InventoryItem::query()
            ->with('post')
            ->orderBy('quantity')
            ->paginate(40);

        return view('tenant.commerce.inventory', [
            'items' => $items,
            'commerce' => CommerceSettings::settings($tenant),
            'stats' => [
                'skus' => InventoryItem::query()->count(),
                'in_stock' => InventoryItem::query()->where('quantity', '>', 0)->count(),
                'sold_out' => InventoryItem::query()->where('track_stock', true)->where('quantity', '<', 1)->count(),
                'low' => InventoryItem::query()
                    ->where('track_stock', true)
                    ->whereColumn('quantity', '<=', 'low_stock_at')
                    ->where('quantity', '>', 0)
                    ->count(),
            ],
        ]);
    }

    public function updateInventory(Request $request, InventoryItem $item): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('commerce.update'), 403);
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:64'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'low_stock_at' => ['required', 'integer', 'min:0', 'max:10000'],
            'track_stock' => ['nullable', 'boolean'],
            'allow_backorder' => ['nullable', 'boolean'],
        ]);

        $item->fill([
            'sku' => $data['sku'] ?? $item->sku,
            'quantity' => $data['quantity'],
            'low_stock_at' => $data['low_stock_at'],
            'track_stock' => $request->boolean('track_stock'),
            'allow_backorder' => $request->boolean('allow_backorder'),
        ]);
        $item->save();

        return back()->with('status', 'Inventory updated for '.($item->post?->title ?? 'item').'.');
    }

    public function finance(): View
    {
        abort_unless(auth()->user()?->hasPermission('commerce.view'), 403);
        $commerce = CommerceSettings::settings(current_tenant());

        $paid = Order::query()->where('payment_status', 'paid');
        $revenue = (float) (clone $paid)->sum('total');
        $tax = (float) (clone $paid)->sum('tax_amount');
        $ordersCount = (clone $paid)->count();
        $customers = Customer::query()->count();
        $avg = $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0;
        $soldOut = InventoryItem::query()->where('track_stock', true)->where('quantity', '<', 1)->count();

        $days = collect(range(6, 0))->map(function (int $ago) {
            $day = Carbon::today()->subDays($ago);

            return [
                'label' => $day->format('D'),
                'date' => $day->format('M j'),
                'value' => (float) Order::query()
                    ->where('payment_status', 'paid')
                    ->whereDate('paid_at', $day)
                    ->sum('total'),
            ];
        });

        $gateways = Order::query()
            ->where('payment_status', 'paid')
            ->select('payment_gateway', DB::raw('COUNT(*) as cnt'), DB::raw('SUM(total) as amount'))
            ->groupBy('payment_gateway')
            ->get();

        return view('tenant.commerce.finance', [
            'commerce' => $commerce,
            'cards' => [
                ['label' => 'Revenue', 'value' => $revenue, 'hint' => $commerce['currency'], 'icon' => 'chart'],
                ['label' => 'GST collected', 'value' => $tax, 'hint' => '18% on sales', 'icon' => 'document'],
                ['label' => 'Paid orders', 'value' => $ordersCount, 'hint' => 'Avg '.$avg, 'icon' => 'cart'],
                ['label' => 'Customers', 'value' => $customers, 'hint' => $soldOut.' sold out SKUs', 'icon' => 'users'],
            ],
            'chart' => [
                'labels' => $days->pluck('label')->all(),
                'dates' => $days->pluck('date')->all(),
                'values' => $days->pluck('value')->all(),
                'total' => $days->sum('value'),
            ],
            'gateways' => $gateways,
            'recent' => Order::query()->with('customer')->where('payment_status', 'paid')->latest('paid_at')->limit(8)->get(),
        ]);
    }
}
