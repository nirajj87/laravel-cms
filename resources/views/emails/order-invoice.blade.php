<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->number }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#0f172a;">
@php
    $site = is_array($tenant->setting('site', [])) ? $tenant->setting('site', []) : [];
    $phone = $site['contact_phone'] ?? ($tenant->phone ?: '');
    $address = $site['contact_address'] ?? ($tenant->address ?: '');
    $tenantEmail = $tenant->email ?: '';
    $customerName = $order->shipping['name'] ?? $customer->name;
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f7;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,.08);">
                {{-- Colorful header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#0f766e 0%,#0e7490 50%,#1d4ed8 100%);padding:28px 28px 24px;color:#ffffff;">
                        <p style="margin:0 0 6px;font-size:12px;letter-spacing:.12em;text-transform:uppercase;opacity:.9;">Order confirmation</p>
                        <h1 style="margin:0;font-size:26px;line-height:1.25;font-weight:700;">{{ $tenant->name }}</h1>
                        <p style="margin:10px 0 0;font-size:14px;opacity:.92;">Invoice {{ $order->number }} · {{ $order->paid_at?->format('M j, Y') ?? $order->created_at->format('M j, Y') }}</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 16px;font-size:16px;line-height:1.5;">Dear <strong>{{ $customerName }}</strong>,</p>
                        <p style="margin:0 0 22px;font-size:15px;line-height:1.6;color:#334155;">
                            Thank you for your order. Your payment has been received and a tax invoice is attached for your records.
                        </p>

                        {{-- Order summary table --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 22px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                            <tr style="background:#f8fafc;">
                                <th align="left" style="padding:12px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:1px solid #e2e8f0;">Detail</th>
                                <th align="right" style="padding:12px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:1px solid #e2e8f0;">Value</th>
                            </tr>
                            <tr>
                                <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">Invoice</td>
                                <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;font-weight:600;">{{ $order->number }}</td>
                            </tr>
                            <tr>
                                <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">Payment</td>
                                <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;font-weight:600;">{{ strtoupper($order->payment_gateway) }} — {{ $order->payment_status }}</td>
                            </tr>
                            <tr>
                                <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">Subtotal</td>
                                <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</td>
                            </tr>
                            <tr>
                                <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">GST ({{ number_format((float) $order->tax_rate, 0) }}%)</td>
                                <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ number_format($order->tax_amount, 2) }} {{ $order->currency }}</td>
                            </tr>
                            <tr style="background:#ecfdf5;">
                                <td style="padding:12px 14px;font-size:15px;font-weight:700;color:#065f46;">Total paid</td>
                                <td align="right" style="padding:12px 14px;font-size:15px;font-weight:700;color:#065f46;">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                            </tr>
                        </table>

                        {{-- Items table --}}
                        <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f766e;">Items</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 22px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                            <tr style="background:#f8fafc;">
                                <th align="left" style="padding:10px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:1px solid #e2e8f0;">Item</th>
                                <th align="center" style="padding:10px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:1px solid #e2e8f0;">Qty</th>
                                <th align="right" style="padding:10px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:1px solid #e2e8f0;">Amount</th>
                            </tr>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $item->title }}</td>
                                    <td align="center" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $item->quantity }}</td>
                                    <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ number_format($item->line_total, 2) }} {{ $order->currency }}</td>
                                </tr>
                            @endforeach
                        </table>

                        {{-- Delivery --}}
                        <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f766e;">Delivery</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 22px;background:#f8fafc;border-radius:12px;">
                            <tr>
                                <td style="padding:14px 16px;font-size:14px;line-height:1.55;color:#334155;">
                                    {{ $customerName }}<br>
                                    {{ $order->shipping['address'] ?? '' }}<br>
                                    @if (!empty($order->shipping['landmark'])){{ $order->shipping['landmark'] }}<br>@endif
                                    Pincode: {{ $order->shipping['pincode'] ?? '' }} · Mobile: {{ $order->shipping['phone'] ?? $customer->phone }}
                                </td>
                            </tr>
                        </table>

                        @if ($plainPassword)
                            <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f766e;">Your login details</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 18px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;">Email</td>
                                    <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;font-size:14px;font-family:Consolas,monospace;">{{ $customer->email }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 14px;font-size:14px;">Password</td>
                                    <td align="right" style="padding:11px 14px;font-size:14px;font-family:Consolas,monospace;">{{ $plainPassword }}</td>
                                </tr>
                            </table>
                        @endif

                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 8px;">
                            <tr>
                                <td style="border-radius:999px;background:#0f766e;">
                                    <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 22px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">
                                        Customer login
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Tenant signature / footer --}}
                <tr>
                    <td style="background:#0f172a;padding:24px 28px;color:#e2e8f0;">
                        <p style="margin:0 0 4px;font-size:15px;font-weight:700;color:#ffffff;">Warm regards,</p>
                        <p style="margin:0 0 12px;font-size:16px;font-weight:700;color:#5eead4;">{{ $tenant->name }}</p>
                        <p style="margin:0;font-size:13px;line-height:1.6;color:#94a3b8;">
                            @if ($address){{ $address }}<br>@endif
                            @if ($phone)Phone: {{ $phone }}<br>@endif
                            @if ($tenantEmail)Email: {{ $tenantEmail }}<br>@endif
                            This is an automated message from {{ $tenant->name }}. Please keep this email for your records.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
