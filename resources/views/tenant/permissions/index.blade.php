@extends('layouts.app')

@section('title', 'Permissions')
@section('kicker', 'Administration')
@section('heading', 'Permissions')

@section('content')
    <p class="mb-4 max-w-2xl text-sm text-slate-600">These permissions come from the module catalog. Assign them on a role. Hiding a menu item never replaces this check.</p>
    <div class="card p-5">
        @include('partials.permission-matrix', ['locked' => true, 'selected' => []])
    </div>
    <p class="mt-4 text-sm"><a class="font-medium text-teal-800" href="{{ route('tenant.roles.index') }}">Assign permissions on a role</a></p>
@endsection
