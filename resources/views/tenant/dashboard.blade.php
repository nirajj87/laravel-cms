@extends('layouts.app')

@section('title', 'Dashboard')
@section('kicker', $tenant->name)
@section('heading', 'Dashboard')

@section('content')
    <div class="mb-6 rounded-3xl border border-teal-900/10 bg-gradient-to-br from-slate-950 via-teal-950 to-slate-900 px-6 py-7 text-white shadow-sm sm:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-200/80">Workspace</p>
        <h2 class="mt-2 max-w-2xl text-2xl font-semibold tracking-tight sm:text-3xl">{{ $tenant->name }}</h2>
        <p class="mt-2 max-w-xl text-sm text-teal-50/75">{{ $tenant->setting('tagline') ?: 'Overview of content, people, and activity in this workspace.' }}</p>
    </div>

    @include('partials.dashboard-stats', [
        'cards' => [
            ['label' => 'Posts', 'value' => $stats['posts'], 'hint' => $stats['published'].' published · '.$stats['drafts'].' drafts', 'icon' => 'document'],
            ['label' => 'Categories', 'value' => $stats['categories'], 'icon' => 'folder'],
            ['label' => 'People', 'value' => $stats['users'], 'icon' => 'users'],
            ['label' => 'Feedback', 'value' => $stats['feedback'], 'hint' => 'Storage '.number_format($stats['storage'] / 1024, 1).' KB', 'icon' => 'chat'],
        ],
        'chart' => $stats['chart'],
    ])

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Quick links</h2>
            </div>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($modules as $module)
                    @if ($module->routeName() && \Illuminate\Support\Facades\Route::has($module->routeName()))
                        <li>
                            <a href="{{ route($module->routeName()) }}" class="flex items-center gap-2 rounded-xl border border-stone-100 px-3 py-2.5 text-sm transition hover:border-teal-200 hover:bg-teal-50/50">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-800">
                                    <x-icon :name="$module->icon ?: 'grid'" class="h-4 w-4" />
                                </span>
                                {{ $module->name }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </section>
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Recent activity</h2>
            @include('partials.activity-list', ['activity' => $activity])
        </section>
    </div>
@endsection
