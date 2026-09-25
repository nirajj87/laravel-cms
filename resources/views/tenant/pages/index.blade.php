@extends('layouts.app')

@section('title', 'Pages')
@section('kicker', 'Content')
@section('heading', 'Pages')

@section('content')
    <div class="mb-4 flex justify-end">
        @permission('pages.create')
            <a class="btn btn-primary" href="{{ route('tenant.pages.create') }}">Add page</a>
        @endpermission
    </div>
    <section class="card divide-y divide-stone-100">
        @forelse ($pages as $page)
            <div class="flex items-center justify-between gap-3 px-5 py-4">
                <div>
                    <p class="font-medium">{{ $page->title }}</p>
                    <p class="text-xs text-slate-500">/page/{{ $page->slug }} · {{ ucfirst($page->status) }}</p>
                </div>
                @permission('pages.edit')
                    <a class="btn btn-secondary" href="{{ route('tenant.pages.edit', $page) }}">Edit</a>
                @endpermission
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-slate-500">No pages yet.</p>
        @endforelse
    </section>
@endsection
