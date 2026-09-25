@extends('layouts.app')

@section('title', $tenant->name)
@section('kicker', 'Workspace')
@section('heading', $tenant->name)

@section('content')
    <div class="mb-6 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('platform.tenants.enter', $tenant) }}">
            @csrf
            <button class="btn btn-primary" type="submit">Open workspace</button>
        </form>
        <a class="btn btn-secondary" href="{{ route('platform.tenants.users.index', $tenant) }}">Users</a>
        <a class="btn btn-secondary" href="{{ route('platform.tenants.edit', $tenant) }}">Edit</a>
        <form method="POST" action="{{ route('platform.tenants.destroy', $tenant) }}" onsubmit="return confirm('Delete this workspace and its users?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit">Delete</button>
        </form>
    </div>

    <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="card p-5 text-sm">
            <dl class="space-y-3">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Slug</dt><dd>{{ $tenant->slug }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Domain</dt><dd>{{ $tenant->domain ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Subdomain</dt><dd>{{ $tenant->subdomain ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd>{{ $tenant->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd>{{ $tenant->status->label() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Users</dt><dd>{{ $tenant->users_count }}</dd></div>
            </dl>
            <h2 class="mt-6 mb-3 font-semibold">Modules</h2>
            <ul class="flex flex-wrap gap-2">
                @foreach ($modules as $module)
                    <li class="rounded-full px-2.5 py-1 text-xs {{ $module->pivot->enabled ? 'bg-teal-50 text-teal-800' : 'bg-stone-100 text-slate-500' }}">
                        {{ $module->name }}
                    </li>
                @endforeach
            </ul>
        </section>
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Workspace activity</h2>
            @include('partials.activity-list', ['activity' => $activity])
        </section>
    </div>
@endsection
