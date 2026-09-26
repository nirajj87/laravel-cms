@extends('public.layout')

@section('content')
<style>
.pay-wrap{max-width:34rem;margin:1.75rem auto 2.5rem;padding:0 1rem;}
.pay-head{text-align:center;margin-bottom:1.25rem;}
.pay-head .site-kicker{margin-bottom:.35rem;}
.pay-head h2{margin:0;font-size:1.55rem;letter-spacing:-.02em;}
.pay-card{
    background:linear-gradient(#fff,#fbfdff);
    border:1px solid rgba(15,23,42,.08);
    border-radius:22px;
    box-shadow:0 18px 40px rgba(15,23,42,.08);
    overflow:hidden;
}
.pay-banner{
    background:linear-gradient(135deg,#0f766e 0%,#0e7490 55%,#0369a1 100%);
    color:#fff;
    padding:1.15rem 1.4rem;
}
.pay-banner p{margin:0;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;opacity:.85;}
.pay-banner strong{display:block;margin-top:.25rem;font-size:1.2rem;}
.pay-body{padding:1.35rem 1.4rem 1.5rem;}
.pay-totals{width:100%;border-collapse:collapse;margin:0 0 1rem;}
.pay-totals td{padding:.45rem 0;font-size:.95rem;border-bottom:1px solid rgba(15,23,42,.06);}
.pay-totals tr:last-child td{border-bottom:0;padding-top:.7rem;font-size:1.05rem;font-weight:700;}
.pay-totals td:last-child{text-align:right;}
.pay-items{margin:0 0 1.1rem;padding:0;list-style:none;}
.pay-items li{display:flex;justify-content:space-between;gap:1rem;padding:.55rem 0;border-bottom:1px dashed rgba(15,23,42,.1);font-size:.92rem;}
.pay-items li:last-child{border-bottom:0;}
.pay-status{display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .7rem;border-radius:999px;background:#ecfdf5;color:#065f46;font-size:.82rem;font-weight:600;margin-bottom:1rem;}
.pay-card label{display:grid;gap:.35rem;margin-bottom:.85rem;font-size:.92rem;}
.pay-methods{display:grid;gap:.55rem;margin:1rem 0;}
.pay-methods label{display:flex;gap:.65rem;align-items:center;margin:0;padding:.75rem .9rem;border:1px solid rgba(15,23,42,.1);border-radius:12px;cursor:pointer;background:#fff;}
.pay-methods label:has(input:checked){border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,.12);}
.cheque-fields,.cash-fields{display:none;margin-top:.5rem;}
.pay-actions{display:flex;justify-content:center;}
</style>

<div class="pay-wrap">
    <div class="pay-head">
        <p class="site-kicker">Payment</p>
        <h2>Order {{ $order->number }}</h2>
    </div>

    @if (session('status'))
        <p style="margin:0 0 1rem;text-align:center;">{{ session('status') }}</p>
    @endif

    <div class="pay-card">
        <div class="pay-banner">
            <p>Amount due</p>
            <strong>{{ number_format($order->total, 2) }} {{ $order->currency }}</strong>
        </div>
        <div class="pay-body">
            <table class="pay-totals">
                <tr>
                    <td>Subtotal</td>
                    <td>{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</td>
                </tr>
                <tr>
                    <td>GST ({{ number_format((float) $order->tax_rate, 0) }}%)</td>
                    <td>{{ number_format($order->tax_amount, 2) }} {{ $order->currency }}</td>
                </tr>
                <tr>
                    <td>Total</td>
                    <td>{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                </tr>
            </table>

            <ul class="pay-items">
                @foreach ($order->items as $item)
                    <li>
                        <span>{{ $item->title }} × {{ $item->quantity }}</span>
                        <span>{{ number_format($item->line_total, 2) }}</span>
                    </li>
                @endforeach
            </ul>

            @if ($order->payment_status === 'paid')
                <div class="pay-status">Already paid</div>
                <div class="pay-actions">
                    <a class="site-btn" href="{{ route('site.payment.thanks', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">View confirmation</a>
                </div>
            @else
                <form method="POST" action="{{ route('site.payment.confirm', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}" id="pay-form">
                    @csrf
                    <p style="margin:0 0 .4rem;font-weight:600;">Select payment method</p>
                    <div class="pay-methods">
                        @foreach ($methods as $value => $label)
                            <label>
                                <input type="radio" name="method" value="{{ $value }}" @checked($loop->first) onchange="togglePayFields(this.value)">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="cash-fields" id="cash-fields">
                        <label>Received by (optional)<input class="site-input" name="cash_received_by" value="{{ old('cash_received_by') }}"></label>
                    </div>

                    <div class="cheque-fields" id="cheque-fields">
                        <label>Cheque number<input class="site-input" name="cheque_number" value="{{ old('cheque_number') }}"></label>
                        <label>Bank name<input class="site-input" name="cheque_bank" value="{{ old('cheque_bank') }}"></label>
                        <label>Cheque date<input class="site-input" type="date" name="cheque_date" value="{{ old('cheque_date') }}"></label>
                    </div>

                    <label>Notes (optional)<textarea class="site-input" name="notes" rows="2">{{ old('notes') }}</textarea></label>
                    <button class="site-btn" type="submit" style="width:100%;justify-content:center;">Save & continue</button>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
function togglePayFields(method) {
    document.getElementById('cash-fields').style.display = method === 'cash' ? 'block' : 'none';
    document.getElementById('cheque-fields').style.display = method === 'cheque' ? 'block' : 'none';
}
togglePayFields(document.querySelector('input[name=method]:checked')?.value || 'cash');
</script>
@endsection
