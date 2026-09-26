@extends('layouts.app')
@section('title', 'Customers')
@section('kicker', 'WooCommerce')
@section('heading', 'Customers')

@section('content')
    <style>
        .woo-stats{display:grid;gap:.85rem;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));margin-bottom:1.1rem;}
        .woo-stat{background:#fff;border:1px solid #e7e5e4;border-radius:14px;padding:1rem 1.1rem;}
        .woo-stat p{margin:0;font-size:.75rem;letter-spacing:.04em;text-transform:uppercase;color:#78716c;}
        .woo-stat strong{display:block;margin-top:.35rem;font-size:1.35rem;color:#0f172a;}
        .woo-table-wrap{background:#fff;border:1px solid #e7e5e4;border-radius:16px;overflow:hidden;}
        .woo-table{width:100%;min-width:48rem;border-collapse:collapse;text-align:left;font-size:.9rem;}
        .woo-table th{padding:.85rem 1rem;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;color:#78716c;background:#fafaf9;border-bottom:1px solid #e7e5e4;}
        .woo-table td{padding:.95rem 1rem;border-bottom:1px solid #f5f5f4;vertical-align:middle;}
        .woo-table tr:last-child td{border-bottom:0;}
        .woo-table tbody tr:hover{background:#fafaf9;}
        .woo-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.78rem;color:#334155;}
        .woo-pill{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;background:#ecfdf5;color:#065f46;font-size:.75rem;font-weight:600;}
    </style>

    <div class="woo-stats">
        <div class="woo-stat">
            <p>Total customers</p>
            <strong>{{ $customers->total() }}</strong>
        </div>
        <div class="woo-stat">
            <p>This page</p>
            <strong>{{ $customers->count() }}</strong>
        </div>
        <div class="woo-stat">
            <p>Store currency</p>
            <strong>{{ $commerce['currency'] ?? 'INR' }}</strong>
        </div>
    </div>

    <div class="woo-table-wrap overflow-x-auto">
        <table class="woo-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Login password</th>
                    <th>Orders</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td>
                        <a class="font-semibold text-teal-800 hover:underline" href="{{ route('tenant.commerce.customers.show', $customer) }}">{{ $customer->name }}</a>
                        <div class="woo-mono mt-0.5">{{ $customer->email }}</div>
                    </td>
                    <td>{{ $customer->phone ?: '—' }}</td>
                    <td>
                        @if ($customer->issued_password)
                            <span class="woo-mono rounded-md bg-stone-100 px-2 py-1">{{ $customer->issued_password }}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td><span class="woo-pill">{{ $customer->orders_count }}</span></td>
                    <td class="text-slate-500">{{ $customer->created_at->format('M j, Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-10 text-center text-slate-500" colspan="5">No customers yet. They appear after guest checkout or registration.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($customers->hasPages())
        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
@endsection
