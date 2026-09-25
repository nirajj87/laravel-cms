@extends('layouts.app')

@section('title', 'Tenants')
@section('kicker', 'Platform')
@section('heading', 'Tenants')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap gap-2">
            <input class="field sm:w-64" type="search" name="q" value="{{ request('q') }}" placeholder="Search name, slug, domain">
            <select class="field sm:w-40" name="status">
                <option value="">Any status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary" type="submit">Filter</button>
        </form>
        <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary">Add tenant</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-stone-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Workspace</th>
                        <th class="px-4 py-3 font-medium">Host</th>
                        <th class="px-4 py-3 font-medium">Users</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($tenants as $tenant)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('platform.tenants.show', $tenant) }}" class="font-medium hover:text-teal-800">{{ $tenant->name }}</a>
                                <p class="text-xs text-slate-500">{{ $tenant->slug }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $tenant->domain ?: ($tenant->subdomain ? $tenant->subdomain.'.'.config('tenancy.base_domain') : '—') }}</td>
                            <td class="px-4 py-3">{{ $tenant->users_count }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $tenant->status->value === 'active' ? 'bg-teal-50 text-teal-800' : 'bg-stone-100 text-slate-600' }}">{{ $tenant->status->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a class="text-sm font-medium text-teal-800" href="{{ route('platform.tenants.edit', $tenant) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No workspaces match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $tenants->links() }}</div>
@endsection
