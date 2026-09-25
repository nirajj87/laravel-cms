<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'A multi-tenant platform for configurable content.' }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico">
    @if (($hostTenant ?? null)?->faviconUrl())
        <link rel="icon" href="{{ $hostTenant->faviconUrl() }}">
    @endif
    @include('partials.assets')
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-stone-100 text-slate-900 antialiased">
    @hasSection('full')
        @yield('full')
    @else
        <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-6">
            <header class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="text-sm font-semibold tracking-tight text-slate-900">
                    {{ ($hostTenant ?? null)?->name ?? config('app.name') }}
                </a>
                @auth
                    <a href="{{ route(auth()->user()->isSuperAdmin() ? 'platform.dashboard' : 'tenant.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
                @endauth
            </header>
            <main class="flex flex-1 items-center justify-center py-10">
                @yield('content')
            </main>
        </div>
    @endif
</body>
</html>
