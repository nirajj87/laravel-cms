@extends('layouts.app')

@section('title', 'Add tenant')
@section('kicker', 'Platform')
@section('heading', 'Add tenant')

@section('content')
    <form method="POST" action="{{ route('platform.tenants.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        @csrf
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Workspace</h2>
            @include('platform.tenants._fields')
        </section>
        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="mb-4 font-semibold">Owner account</h2>
                <x-field label="Admin name" name="admin_name">
                    <input class="field" name="admin_name" value="{{ old('admin_name') }}" required>
                </x-field>
                <x-field label="Admin email" name="admin_email">
                    <input class="field" type="email" name="admin_email" value="{{ old('admin_email') }}" required>
                </x-field>
                <x-field label="Password" name="password">
                    <input class="field" type="password" name="password" required>
                </x-field>
                <x-field label="Confirm password" name="password_confirmation">
                    <input class="field" type="password" name="password_confirmation" required>
                </x-field>
            </section>
            <section class="card p-5">
                <h2 class="mb-1 font-semibold">Modules</h2>
                <p class="mb-4 text-sm text-slate-500">Core modules stay on. Everything else can be enabled per workspace.</p>
                @include('partials.module-picker')
            </section>
            <button class="btn btn-primary" type="submit">Create workspace</button>
        </div>
    </form>
@endsection
