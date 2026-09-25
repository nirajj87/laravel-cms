@extends('layouts.app')

@section('title', 'Dashboard')
@section('kicker', $tenant->name)
@section('heading', 'Dashboard')

@section('content')
    @include('partials.dashboard-stats', [
        'cards' => [
            ['label' => 'Posts', 'value' => $stats['posts'], 'hint' => $stats['published'].' published · '.$stats['drafts'].' drafts'],
            ['label' => 'Categories', 'value' => $stats['categories']],
            ['label' => 'People', 'value' => $stats['users']],
            ['label' => 'Feedback', 'value' => $stats['feedback'], 'hint' => 'Storage '.number_format($stats['storage'] / 1024, 1).' KB'],
        ],
        'chart' => $stats['chart'],
    ])

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Modules</h2>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($modules as $module)
                    @if ($module->routeName() && \Illuminate\Support\Facades\Route::has($module->routeName()))
                        <li>
                            <a href="{{ route($module->routeName()) }}" class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm hover:bg-stone-50">
                                <x-icon :name="$module->icon ?: 'grid'" class="h-4 w-4 text-teal-800" />
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
