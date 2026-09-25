@extends('layouts.app')

@section('title', $member->exists ? 'Edit user' : 'Add user')
@section('kicker', $tenant->name)
@section('heading', $member->exists ? 'Edit user' : 'Add user')

@section('content')
    @include('partials.user-form', [
        'action' => $member->exists
            ? route('platform.tenants.users.update', [$tenant, $member])
            : route('platform.tenants.users.store', $tenant),
    ])
@endsection
