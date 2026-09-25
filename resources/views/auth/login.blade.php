@extends('layouts.guest')

@section('full')
    <div class="grid min-h-screen lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden bg-slate-950 text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="pointer-events-none absolute inset-0"
                 style="background:
                    radial-gradient(900px 420px at 10% -10%, rgba(15,118,110,.55), transparent 60%),
                    radial-gradient(700px 360px at 90% 20%, rgba(13,148,136,.28), transparent 55%),
                    linear-gradient(165deg, #042f2e, #020617 70%);"></div>
            <div class="relative">
                <a href="{{ route('home') }}" class="text-sm font-semibold tracking-wide text-teal-100/90">
                    {{ ($hostTenant ?? null)?->name ?? config('app.name') }}
                </a>
                <h1 class="mt-16 max-w-md text-4xl font-semibold tracking-tight text-white">
                    One login for platform and workspace.
                </h1>
                <p class="mt-4 max-w-sm text-base leading-relaxed text-teal-50/75">
                    Manage tenants, content forms, themes, and public sites from the same account.
                </p>
            </div>
            <p class="relative text-sm text-slate-400">Secure multi-tenant content platform</p>
        </aside>

        <main class="flex flex-col justify-center px-4 py-10 sm:px-8">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <a href="{{ route('home') }}" class="text-sm font-semibold text-teal-800">
                        {{ ($hostTenant ?? null)?->name ?? config('app.name') }}
                    </a>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-800">Welcome back</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Sign in</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ ($hostTenant ?? null)?->name ? 'Continue to '.$hostTenant->name.'.' : 'Platform and workspace accounts use the same page.' }}
                    </p>

                    <form method="POST" action="{{ route('login') }}" class="mt-7">
                        @csrf
                        <x-field label="Email" name="email">
                            <input class="field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                        </x-field>
                        <x-field label="Password" name="password">
                            <input class="field" type="password" name="password" required autocomplete="current-password">
                        </x-field>
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="remember" value="1" class="rounded border-stone-300 text-teal-800" @checked(old('remember'))>
                                Remember this browser
                            </label>
                            <a class="text-sm font-medium text-teal-800 hover:underline" href="{{ route('password.request') }}">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary w-full py-2.5" type="submit">Sign in</button>
                    </form>

                    @if (app()->environment('local'))
                        <details class="mt-6 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-600">
                            <summary class="cursor-pointer font-medium text-slate-800">Local demo accounts</summary>
                            <div class="mt-3 space-y-1.5">
                                <p>Super admin: <span class="font-mono text-xs">admin@platform.test</span></p>
                                <p>Northwind: <span class="font-mono text-xs">owner@northwind.test</span></p>
                                <p>Meridian: <span class="font-mono text-xs">owner@meridian.test</span></p>
                                <p>Sable: <span class="font-mono text-xs">owner@sable.test</span></p>
                                <p class="pt-1 text-xs text-slate-500">Password for all: <span class="font-mono">password</span></p>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </main>
    </div>
@endsection
