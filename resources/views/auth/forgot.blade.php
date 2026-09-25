@extends('layouts.guest')

@section('content')
    <div class="card w-full max-w-md p-6 sm:p-8">
        <h1 class="text-2xl font-semibold tracking-tight">Reset password</h1>
        <p class="mt-1 text-sm text-slate-600">We will email a link if the account exists. The message is the same either way.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-6">
            @csrf
            <x-field label="Email" name="email">
                <input class="field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </x-field>
            <button class="btn btn-primary w-full" type="submit">Email reset link</button>
        </form>
        <p class="mt-4 text-sm"><a class="text-teal-800" href="{{ route('login') }}">Back to sign in</a></p>
    </div>
@endsection
