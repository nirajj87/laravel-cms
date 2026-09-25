@extends('layouts.app')

@section('title', 'Settings')
@section('kicker', $tenant->name)
@section('heading', 'Settings')

@section('content')
    <form method="POST" action="{{ route('tenant.settings.update') }}" class="card max-w-2xl p-5">
        @csrf
        @method('PUT')
        <x-field label="Public email" name="email">
            <input class="field" type="email" name="email" value="{{ old('email', $tenant->email) }}">
        </x-field>
        <x-field label="Phone" name="phone">
            <input class="field" name="phone" value="{{ old('phone', $tenant->phone) }}">
        </x-field>
        <x-field label="Address" name="address">
            <textarea class="field" name="address" rows="3">{{ old('address', $tenant->address) }}</textarea>
        </x-field>
        <x-field label="Tagline" name="tagline">
            <input class="field" name="tagline" value="{{ old('tagline', $tenant->setting('tagline')) }}">
        </x-field>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Timezone" name="timezone">
                <input class="field" name="timezone" value="{{ old('timezone', $tenant->setting('timezone', 'UTC')) }}" required>
            </x-field>
            <x-field label="Accent color" name="primary_color">
                <input class="field" name="primary_color" value="{{ old('primary_color', $tenant->setting('primary_color', '#0f766e')) }}" required>
            </x-field>
        </div>
        @permission('settings.update')
            <button class="btn btn-primary" type="submit">Save settings</button>
        @else
            <p class="text-sm text-slate-500">You can view these settings. Updating them requires the settings.update permission.</p>
        @endpermission
    </form>
@endsection
