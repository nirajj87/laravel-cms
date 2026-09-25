@extends('layouts.app')

@section('title', $member->exists ? 'Edit user' : 'Add user')
@section('kicker', 'Administration')
@section('heading', $member->exists ? 'Edit user' : 'Add user')

@section('content')
    @include('partials.user-form', [
        'action' => $member->exists ? route('tenant.users.update', $member) : route('tenant.users.store'),
    ])
@endsection
