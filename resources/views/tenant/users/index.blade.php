@extends('layouts.app')

@section('title', 'Users')
@section('kicker', 'Administration')
@section('heading', 'Users')

@section('content')
    <div class="mb-4 flex justify-end">
        @permission('users.create')
            <a class="btn btn-primary" href="{{ route('tenant.users.create') }}">Add user</a>
        @endpermission
    </div>
    @include('partials.user-table', [
        'users' => $users,
        'editRoute' => fn ($user) => route('tenant.users.edit', $user),
        'deleteRoute' => fn ($user) => route('tenant.users.destroy', $user),
    ])
@endsection
