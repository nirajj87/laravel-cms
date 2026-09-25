@extends('layouts.guest')

@section('content')
    <div class="card w-full max-w-md p-6 sm:p-8">
        <h1 class="text-2xl font-semibold tracking-tight">Choose a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-field label="Email" name="email">
                <input class="field" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
            </x-field>
            <x-field label="New password" name="password">
                <input class="field" type="password" name="password" required autocomplete="new-password">
            </x-field>
            <x-field label="Confirm password" name="password_confirmation">
                <input class="field" type="password" name="password_confirmation" required autocomplete="new-password">
            </x-field>
            <button class="btn btn-primary w-full" type="submit">Update password</button>
        </form>
    </div>
@endsection
