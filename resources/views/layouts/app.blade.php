<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico">
    @php $brand = $currentTenant ?? $hostTenant ?? null; @endphp
    @if ($brand?->faviconUrl())
        <link rel="icon" href="{{ $brand->faviconUrl() }}">
    @endif
    @include('partials.assets')
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-stone-100 text-slate-900 antialiased" x-data="{ sidebar: false }">
    <div class="lg:grid lg:min-h-screen lg:grid-cols-[260px_1fr]">
        <aside class="border-b border-slate-800 bg-slate-950 text-slate-200 lg:border-b-0 lg:border-r" :class="sidebar ? 'block' : 'hidden lg:block'">
            <div class="flex items-center justify-between px-5 py-5">
                <a href="{{ auth()->user()->isSuperAdmin() && ! request()->routeIs('tenant.*') ? route('platform.dashboard') : route('tenant.dashboard') }}" class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">{{ $currentTenant->name ?? config('app.name') }}</p>
                    <p class="truncate text-xs text-slate-400">
                        {{ auth()->user()->isSuperAdmin() && ! request()->routeIs('tenant.*') ? 'Platform' : 'Workspace' }}
                    </p>
                </a>
            </div>
            <nav class="space-y-1 px-3 pb-6">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm {{ request()->routeIs($item['active']) ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        <x-icon :name="$item['icon']" class="h-4 w-4" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0">
            <header class="flex items-center justify-between gap-4 border-b border-stone-200 bg-white px-4 py-3 sm:px-6">
                <button type="button" class="btn btn-secondary lg:hidden" @click="sidebar = !sidebar">Menu</button>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm text-slate-500">@yield('kicker', 'Workspace')</p>
                    <h1 class="truncate text-lg font-semibold">@yield('heading', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->isSuperAdmin() ? 'Super admin' : auth()->user()->roles->pluck('name')->join(', ') }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-secondary" type="submit">Sign out</button>
                    </form>
                </div>
            </header>

            @if (auth()->user()->isSuperAdmin() && request()->routeIs('tenant.*') && $currentTenant)
                <div class="flex flex-wrap items-center justify-between gap-3 bg-amber-50 px-4 py-3 text-sm text-amber-950 sm:px-6">
                    <p>You are managing <strong>{{ $currentTenant->name }}</strong> as super admin.</p>
                    <form method="POST" action="{{ route('tenant.exit') }}">
                        @csrf
                        <button class="btn btn-secondary" type="submit">Exit workspace</button>
                    </form>
                </div>
            @endif

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900" x-data="{ show: true }" x-show="show">
                        <div class="flex items-start justify-between gap-3">
                            <p>{{ session('status') }}</p>
                            <button type="button" class="text-teal-800" @click="show = false">Close</button>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
