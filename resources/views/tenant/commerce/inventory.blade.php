@extends('layouts.app')
@section('title', 'Inventory')
@section('kicker', 'WooCommerce')
@section('heading', 'Inventory')

@section('content')
<style>
.inv-stats{display:grid;gap:.85rem;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));margin-bottom:1.1rem;}
.inv-stat{background:#fff;border:1px solid #e7e5e4;border-radius:14px;padding:1rem 1.1rem;}
.inv-stat p{margin:0;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;}
.inv-stat strong{display:block;margin-top:.35rem;font-size:1.35rem;}
.inv-card{background:#fff;border:1px solid #e7e5e4;border-radius:16px;padding:1.1rem 1.2rem;margin-bottom:.85rem;}
.inv-badge{display:inline-flex;padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;}
.inv-ok{background:#ecfdf5;color:#065f46;}
.inv-low{background:#fff7ed;color:#9a3412;}
.inv-out{background:#fef2f2;color:#991b1b;}
.inv-grid{display:grid;gap:.75rem;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));align-items:end;}
.inv-grid label{display:grid;gap:.3rem;font-size:.78rem;color:#64748b;}
.inv-grid input[type=text],.inv-grid input[type=number]{border:1px solid #e7e5e4;border-radius:8px;padding:.45rem .6rem;font-size:.9rem;}
</style>

<div class="inv-stats">
    <div class="inv-stat"><p>SKUs</p><strong>{{ $stats['skus'] }}</strong></div>
    <div class="inv-stat"><p>In stock</p><strong>{{ $stats['in_stock'] }}</strong></div>
    <div class="inv-stat"><p>Low stock</p><strong>{{ $stats['low'] }}</strong></div>
    <div class="inv-stat"><p>Sold out</p><strong>{{ $stats['sold_out'] }}</strong></div>
</div>

<p class="mb-4 text-sm text-slate-500">Stock drops when payment succeeds. At 0 the product shows <strong>Sold out</strong> on the storefront.</p>

@forelse ($items as $item)
    @php
        $status = ! $item->track_stock ? 'Not tracked' : ($item->isSoldOut() ? 'Sold out' : ($item->isLowStock() ? 'Low stock' : 'In stock'));
        $badge = ! $item->track_stock ? 'inv-ok' : ($item->isSoldOut() ? 'inv-out' : ($item->isLowStock() ? 'inv-low' : 'inv-ok'));
    @endphp
    <div class="inv-card">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="m-0 text-base font-semibold text-slate-900">{{ $item->post?->title ?? 'Deleted product' }}</h2>
                <p class="m-0 text-xs text-slate-400">Post #{{ $item->post_id }}</p>
            </div>
            <span class="inv-badge {{ $badge }}">{{ $status }}</span>
        </div>
        <form method="POST" action="{{ route('tenant.commerce.inventory.update', $item) }}" class="inv-grid">
            @csrf
            @method('PUT')
            <label>SKU<input type="text" name="sku" value="{{ $item->sku }}"></label>
            <label>Quantity<input type="number" min="0" name="quantity" value="{{ $item->quantity }}" required></label>
            <label>Low stock at<input type="number" min="0" name="low_stock_at" value="{{ $item->low_stock_at }}" required></label>
            <label class="self-center"><span class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="track_stock" value="1" @checked($item->track_stock)> Track stock</span></label>
            <label class="self-center"><span class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="allow_backorder" value="1" @checked($item->allow_backorder)> Allow backorder</span></label>
            <button class="btn btn-primary" type="submit">Save</button>
        </form>
    </div>
@empty
    <div class="inv-card text-center text-slate-500">No inventory yet. Products with a price field get a stock row when you open this page.</div>
@endforelse

@if ($items->hasPages())
    <div class="mt-4">{{ $items->links() }}</div>
@endif
@endsection
