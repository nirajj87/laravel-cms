<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme['mode'] ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle ?? $tenant->name }}</title>
    <link rel="icon" href="{{ $tenant->faviconUrl() ?: asset('favicon.svg') }}" type="{{ $tenant->faviconUrl() ? 'image/png' : 'image/svg+xml' }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @include('public._meta')
    @include('public._analytics')
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|source-serif-4:400,600" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|source-serif-4:400,600"></noscript>
    @include('partials.assets')
    <style>{!! \App\Support\SiteTheme::css($theme ?? [], $siteSettings ?? []) !!}</style>
</head>
<body class="site-body {{ ($theme['page_fade'] ?? false) ? 'site-fade' : '' }}">
    @if (! empty($preview))
        <div style="background:#fef3c7;color:#78350f;text-align:center;padding:.6rem 1rem;">Preview. This page is not public.</div>
    @endif
    <header class="site-header" style="border-bottom:1px solid var(--site-card-border);">
        <div class="site-wrap site-bar">
            @include('public.region', ['region' => 'header', 'bare' => true])
            @php
                $commerceOn = \App\Support\CommerceSettings::cartEnabled($tenant);
                $customer = auth('customer')->user();
            @endphp
            @if ($commerceOn)
                <nav class="site-nav" aria-label="Shop" style="margin-left:.5rem;">
                    <a href="{{ route('site.cart.show', ['siteTenant' => $tenant->slug]) }}">Cart</a>
                    @if ($customer)
                        <a href="{{ route('site.account.dashboard', ['siteTenant' => $tenant->slug]) }}">Account</a>
                    @endif
                </nav>
            @endif
            <button class="site-menu-btn site-btn site-btn-outline" type="button" onclick="document.querySelector('.site-nav')?.classList.toggle('is-open')" style="display:none;">Menu</button>
        </div>
        @if (! empty($siteSettings['custom_header']))
            <div class="site-wrap" style="padding-bottom:1rem;">{!! $siteSettings['custom_header'] !!}</div>
        @endif
    </header>
    <main class="site-main site-wrap" style="padding-bottom:2.5rem;">
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="site-wrap">
            @include('public.region', ['region' => 'footer'])
            @if (! empty($siteSettings['custom_footer']))
                <div style="margin-top:1rem;">{!! $siteSettings['custom_footer'] !!}</div>
            @endif
        </div>
    </footer>
    <style>@media (max-width:700px){.site-menu-btn{display:inline-flex!important;}}</style>
</body>
</html>