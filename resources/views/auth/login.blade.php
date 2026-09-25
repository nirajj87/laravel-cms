@extends('layouts.guest')

@section('content')
    <div class="card w-full max-w-md p-6 sm:p-8">
        <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
        <p class="mt-1 text-sm text-slate-600">
            {{ ($hostTenant ?? null)?->name ? 'Continue to '.$hostTenant->name.'.' : 'Platform and workspace accounts use the same page.' }}
        </p>

        <form method="POST" action="{{ route('login') }}" class="mt-6">
            @csrf
            <x-field label="Email" name="email">
                <input class="field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </x-field>
            <x-field label="Password" name="password">
                <input class="field" type="password" name="password" required autocomplete="current-password">
            </x-field>
            <label class="mb-5 flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-stone-300 text-teal-800" @checked(old('remember'))>
                Remember this browser
            </label>
            <button class="btn btn-primary w-full" type="submit">Sign in</button>
        </form>
        <p class="mt-4 text-sm"><a class="text-teal-800" href="{{ route('password.request') }}">Forgot password?</a></p>

        @if (app()->environment('local'))
            <div class="mt-6 rounded-xl bg-stone-50 p-4 text-sm text-slate-600">
                <p class="font-medium text-slate-800">Local accounts</p>
                <p class="mt-2">Super admin: admin@platform.test / password</p>
                <p>Workspace owner: owner@northwind.test / password</p>
                <p>Editor: editor@northwind.test / password</p>
                <p>Meridian: owner@meridian.test / password</p>
                <p>Sable Press: owner@sable.test / password</p>
            </div>
        @endif
    </div>
@endsection
