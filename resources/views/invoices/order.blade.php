<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    <style>
        body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:12px;color:#0f172a;margin:32px;}
        h1{font-size:22px;margin:0 0 4px;}
        .muted{color:#64748b;}
        .row{display:flex;justify-content:space-between;gap:24px;margin-bottom:24px;}
        table{width:100%;border-collapse:collapse;margin-top:16px;}
        th,td{padding:8px 6px;border-bottom:1px solid #e2e8f0;text-align:left;}
        th{font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;}
        .totals{margin-top:16px;width:280px;margin-left:auto;}
        .totals td{border:0;padding:4px 0;}
        .totals .grand{font-size:14px;font-weight:700;border-top:1px solid #cbd5e1;padding-top:8px;}
        .badge{display:inline-block;padding:3px 8px;border-radius:999px;background:#ecfdf5;color:#065f46;font-size:10px;font-weight:700;}
    </style>
</head>
<body>
    <div class="row">
        <div>
            <h1>{{ $tenant->name }}</h1>
            <p class="muted">Tax invoice</p>
        </div>
        <div style="text-align:right;">
            <strong>Invoice {{ $order->number }}</strong><br>
            <span class="muted">{{ $order->paid_at?->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}</span><br>
            <span class="badge">{{ strtoupper($order->payment_status) }}</span>
        </div>
    </div>

    <div class="row">
        <div>
            <strong>Bill to</strong><br>
            {{ $order->shipping['name'] ?? $order->customer?->name }}<br>
            {{ $order->customer?->email }}<br>
            {{ $order->shipping['phone'] ?? $order->customer?->phone }}<br>
            {{ $order->shipping['address'] ?? '' }}<br>
            @if (!empty($order->shipping['landmark'])){{ $order->shipping['landmark'] }}<br>@endif
            {{ $order->shipping['pincode'] ?? '' }}
        </div>
        <div style="text-align:right;">
            <strong>Payment</strong><br>
            Method: {{ strtoupper($order->payment_gateway) }}<br>
            Currency: {{ $order->currency }}<br>
            GST: {{ number_format((float) $order->tax_rate, 2) }}%
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Subtotal</td>
            <td style="text-align:right;">{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</td>
        </tr>
        <tr>
            <td class="muted">GST ({{ number_format((float) $order->tax_rate, 2) }}%)</td>
            <td style="text-align:right;">{{ number_format($order->tax_amount, 2) }} {{ $order->currency }}</td>
        </tr>
        <tr class="grand">
            <td>Grand total</td>
            <td style="text-align:right;">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
        </tr>
    </table>

    <p class="muted" style="margin-top:32px;">This is a computer-generated invoice including GST at {{ number_format((float) $order->tax_rate, 2) }}%.</p>
</body>
</html>
