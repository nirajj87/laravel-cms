<style>
.cust-shell{display:grid;gap:1.25rem;grid-template-columns:220px minmax(0,1fr);align-items:start;margin-top:1.25rem;}
@media (max-width:860px){.cust-shell{grid-template-columns:1fr;}}
.cust-side{background:#0f172a;color:#e2e8f0;border-radius:18px;padding:1.15rem 1rem;position:sticky;top:1rem;}
.cust-side .brand{padding:.35rem .65rem 1rem;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:.75rem;}
.cust-side .brand p{margin:0;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;}
.cust-side .brand strong{display:block;margin-top:.35rem;font-size:1rem;color:#fff;word-break:break-word;}
.cust-nav{display:grid;gap:.25rem;}
.cust-nav a{display:flex;align-items:center;gap:.55rem;padding:.65rem .75rem;border-radius:10px;color:#cbd5e1;text-decoration:none;font-size:.92rem;}
.cust-nav a:hover,.cust-nav a.is-active{background:rgba(255,255,255,.08);color:#fff;}
.cust-side form{margin:.85rem .35rem 0;}
.cust-side button{width:100%;justify-content:center;}
.cust-main{min-width:0;}
.cust-top{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.15rem;}
.cust-top .site-kicker{margin:0 0 .25rem;}
.cust-top h2{margin:0;font-size:1.55rem;letter-spacing:-.02em;}
.cust-stats{display:grid;gap:.85rem;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:1.15rem;}
.cust-stat{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:16px;padding:1rem 1.1rem;box-shadow:0 10px 24px rgba(15,23,42,.04);}
.cust-stat p{margin:0;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;color:#64748b;}
.cust-stat strong{display:block;margin-top:.4rem;font-size:1.35rem;color:#0f172a;}
.cust-panel{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:16px;box-shadow:0 10px 24px rgba(15,23,42,.04);overflow:hidden;}
.cust-panel-h{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.15rem;border-bottom:1px solid rgba(15,23,42,.06);}
.cust-panel-h h3{margin:0;font-size:1rem;}
.cust-table-wrap{overflow-x:auto;}
.cust-table{width:100%;min-width:36rem;border-collapse:collapse;font-size:.9rem;}
.cust-table th{padding:.8rem 1.1rem;text-align:left;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;color:#64748b;background:#f8fafc;border-bottom:1px solid rgba(15,23,42,.06);}
.cust-table td{padding:.9rem 1.1rem;border-bottom:1px solid rgba(15,23,42,.05);vertical-align:middle;}
.cust-table tr:last-child td{border-bottom:0;}
.cust-table tbody tr:hover{background:#f8fafc;}
.cust-badge{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;text-transform:capitalize;}
.cust-badge-paid{background:#ecfdf5;color:#065f46;}
.cust-badge-pending{background:#fff7ed;color:#9a3412;}
.cust-badge-failed{background:#fef2f2;color:#991b1b;}
.cust-badge-default{background:#f1f5f9;color:#475569;}
.cust-empty{padding:2rem 1.15rem;text-align:center;color:#64748b;}
.cust-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));padding:1.15rem;}
.cust-meta p{margin:0 0 .2rem;font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b;}
.cust-meta strong,.cust-meta span{font-size:.98rem;color:#0f172a;}
</style>

@php
    $dashActive = request()->routeIs('site.account.dashboard');
    $ordersActive = request()->routeIs('site.account.orders*');
@endphp

<aside class="cust-side">
    <div class="brand">
        <p>Customer portal</p>
        <strong>{{ $customer->name }}</strong>
    </div>
    <nav class="cust-nav" aria-label="Account">
        <a class="{{ $dashActive ? 'is-active' : '' }}" href="{{ route('site.account.dashboard', ['siteTenant' => $tenant->slug]) }}">Dashboard</a>
        <a class="{{ $ordersActive ? 'is-active' : '' }}" href="{{ route('site.account.orders', ['siteTenant' => $tenant->slug]) }}">Orders</a>
        <a href="{{ route('site.cart.show', ['siteTenant' => $tenant->slug]) }}">Cart{{ isset($cartCount) ? ' ('.$cartCount.')' : '' }}</a>
        <a href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">Back to shop</a>
    </nav>
    <form method="POST" action="{{ route('site.account.logout', ['siteTenant' => $tenant->slug]) }}">
        @csrf
        <button class="site-btn site-btn-outline" type="submit" style="border-color:rgba(255,255,255,.2);color:#fff;">Sign out</button>
    </form>
</aside>
