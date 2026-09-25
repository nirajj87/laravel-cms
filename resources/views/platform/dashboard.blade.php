@extends('layouts.app')

@section('title', 'Platform')
@section('kicker', 'Platform')
@section('heading', 'Overview')

@section('content')
    @include('partials.dashboard-stats', [
        'cards' => [
            ['label' => 'Workspaces', 'value' => $summary['tenants'], 'hint' => $summary['active_tenants'].' active'],
            ['label' => 'Users', 'value' => $summary['users']],
            ['label' => 'Posts', 'value' => $summary['posts'], 'hint' => ($summary['backup']?->status->label() ?? 'No backup').' · '.($summary['backup']?->created_at?->diffForHumans() ?? 'never')],
            ['label' => 'Storage', 'value' => (int) round($summary['storage'] / 1024), 'hint' => 'Kilobytes used'],
        ],
        'chart' => $summary['chart'],
    ])

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Recent workspaces</h2>
                <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary">Add tenant</a>
            </div>
            <ul class="divide-y divide-stone-100">
                @forelse ($recentTenants as $tenant)
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <a href="{{ route('platform.tenants.show', $tenant) }}" class="font-medium hover:text-teal-800">{{ $tenant->name }}</a>
                            <p class="text-xs text-slate-500">{{ $tenant->slug }}</p>
                        </div>
                        <span class="rounded-full px-2 py-1 text-xs {{ $tenant->status->value === 'active' ? 'bg-teal-50 text-teal-800' : 'bg-stone-100 text-slate-600' }}">{{ $tenant->status->label() }}</span>
                    </li>
                @empty
                    <li class="py-6 text-sm text-slate-500">No workspaces yet.</li>
                @endforelse
            </ul>
        </section>
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Recent sign-ins</h2>
            <ul class="divide-y divide-stone-100 text-sm">
                @forelse ($summary['logins'] as $login)
                    <li class="py-2">{{ $login->user?->name ?? 'Unknown' }} · {{ $login->created_at?->diffForHumans() }}</li>
                @empty
                    <li class="py-2 text-slate-500">No sign-ins yet.</li>
                @endforelse
            </ul>
            <h2 class="mb-4 mt-6 font-semibold">Activity</h2>
            @include('partials.activity-list', ['activity' => $activity])
        </section>
    </div>
@endsection
