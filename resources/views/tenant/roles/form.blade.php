@extends('layouts.app')

@section('title', $role->exists ? 'Edit role' : 'Add role')
@section('kicker', 'Administration')
@section('heading', $role->exists ? $role->name : 'Add role')

@section('content')
    <form method="POST" action="{{ $role->exists ? route('tenant.roles.update', $role) : route('tenant.roles.store') }}" class="grid gap-6 lg:grid-cols-[320px_1fr]">
        @csrf
        @if ($role->exists)
            @method('PUT')
        @endif
        <section class="card h-fit p-5">
            <x-field label="Name" name="name">
                <input class="field" name="name" value="{{ old('name', $role->name) }}" required>
            </x-field>
            @unless ($role->is_system)
                <x-field label="Slug" name="slug">
                    <input class="field" name="slug" value="{{ old('slug', $role->slug) }}" placeholder="Generated from the name">
                </x-field>
            @endunless
            <x-field label="Description" name="description">
                <textarea class="field" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
            </x-field>
            @if ($role->isOwner())
                <p class="mb-4 text-sm text-slate-500">The owner role always keeps every permission for enabled modules.</p>
            @endif
            <button class="btn btn-primary" type="submit">{{ $role->exists ? 'Save role' : 'Create role' }}</button>
        </section>
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Permissions</h2>
            @include('partials.permission-matrix', ['locked' => $role->isOwner(), 'selected' => $selected])
        </section>
    </form>
@endsection
