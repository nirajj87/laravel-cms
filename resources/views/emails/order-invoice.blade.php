<x-mail::message>
# Thank you for your order

**Invoice:** {{ $order->number }}  
**Payment:** {{ strtoupper($order->payment_gateway) }} — {{ $order->payment_status }}  
**Subtotal:** {{ number_format($order->subtotal, 2) }} {{ $order->currency }}  
**GST ({{ number_format((float) $order->tax_rate, 2) }}%):** {{ number_format($order->tax_amount, 2) }} {{ $order->currency }}  
**Total:** {{ number_format($order->total, 2) }} {{ $order->currency }}  
**Date:** {{ $order->paid_at?->format('M j, Y H:i') ?? $order->created_at->format('M j, Y H:i') }}

A tax invoice is attached to this email for your records.

## Items
@foreach ($order->items as $item)
- {{ $item->title }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2) }} {{ $order->currency }}
@endforeach

## Delivery
{{ $order->shipping['name'] ?? $customer->name }}  
{{ $order->shipping['address'] ?? '' }}  
@if (!empty($order->shipping['landmark'])){{ $order->shipping['landmark'] }}  
@endif
Pincode: {{ $order->shipping['pincode'] ?? '' }} · Mobile: {{ $order->shipping['phone'] ?? $customer->phone }}

@if ($plainPassword)
## Your login details
Use these details anytime to check your orders and download invoices:

**Email:** {{ $customer->email }}  
**Password:** {{ $plainPassword }}

<x-mail::button :url="$loginUrl">
Customer login
</x-mail::button>
@else
You can review this order anytime from your account:

<x-mail::button :url="$loginUrl">
Customer login
</x-mail::button>
@endif

Thanks,<br>
{{ $tenant->name }}
</x-mail::message>
