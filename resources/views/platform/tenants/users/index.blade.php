@extends('layouts.app')

@section('title', $tenant->name.' users')
@section('kicker', $tenant->name)
@section('heading', 'Users')

@section('content')
    <div class="mb-4 flex justify-between gap-3">
        <a class="text-sm text-teal-800" href="{{ route('platform.tenants.show', $tenant) }}">Back to workspace</a>
        <a class="btn btn-primary" href="{{ route('platform.tenants.users.create', $tenant) }}">Add user</a>
    </div>
    @include('partials.user-table', [
        'users' => $users,
        'editRoute' => fn ($user) => route('platform.tenants.users.edit', [$tenant, $user]),
        'deleteRoute' => fn ($user) => route('platform.tenants.users.destroy', [$tenant, $user]),
    ])
@endsection
