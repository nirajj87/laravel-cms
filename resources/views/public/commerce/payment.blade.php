@extends('public.layout')

@section('content')
<style>
.pay-card{background:#fff;border:1px solid var(--site-card-border);border-radius:calc(var(--site-radius)+4px);box-shadow:var(--site-card-shadow);padding:1.25rem 1.4rem;max-width:40rem;}
.pay-card label{display:grid;gap:.35rem;margin-bottom:.85rem;font-size:.92rem;}
.pay-methods{display:grid;gap:.55rem;margin:1rem 0;}
.pay-methods label{display:flex;gap:.65rem;align-items:center;margin:0;padding:.7rem .85rem;border:1px solid rgba(15,23,42,.12);border-radius:10px;cursor:pointer;}
.cheque-fields,.cash-fields{display:none;margin-top:.5rem;}
</style>

<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Payment</p>
        <h2>Order {{ $order->number }}</h2>
    </div>
</section>

@if (session('status'))
    <p style="margin-bottom:1rem;">{{ session('status') }}</p>
@endif

<div class="pay-card">
    <p style="margin:0 0 .35rem;">Subtotal: {{ number_format($order->subtotal, 2) }} {{ $order->currency }}</p>
    <p style="margin:0 0 .35rem;">GST ({{ number_format((float) $order->tax_rate, 0) }}%): {{ number_format($order->tax_amount, 2) }} {{ $order->currency }}</p>
    <p style="margin:0 0 .5rem;">Total: <strong>{{ number_format($order->total, 2) }} {{ $order->currency }}</strong></p>
    <ul style="margin:0 0 1rem;padding-left:1.1rem;">
        @foreach ($order->items as $item)
            <li>{{ $item->title }} × {{ $item->quantity }} — {{ number_format($item->line_total, 2) }}</li>
        @endforeach
    </ul>

    @if ($order->payment_status === 'paid')
        <p>Already paid.</p>
        <a class="site-btn" href="{{ route('site.payment.thanks', ['siteTenant' => $tenant->slug, 'order' => $order->id]) }}">View confirmation</a>
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

<script>
function togglePayFields(method) {
    document.getElementById('cash-fields').style.display = method === 'cash' ? 'block' : 'none';
    document.getElementById('cheque-fields').style.display = method === 'cheque' ? 'block' : 'none';
}
togglePayFields(document.querySelector('input[name=method]:checked')?.value || 'cash');
</script>
@endsection