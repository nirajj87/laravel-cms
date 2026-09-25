@extends('layouts.app')

@php
    $hub = $hub ?? 'tenant.posts.types';
    $isBuilder = $hub === 'tenant.form-builder';
@endphp

@section('title', $isBuilder ? 'Form Builder' : 'Content types')
@section('kicker', $isBuilder ? 'Design' : 'Content')
@section('heading', $isBuilder ? 'Form Builder' : 'Content types')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <p class="max-w-2xl text-sm text-slate-600">
            @if ($isBuilder)
                Build forms without hard-coded fields. Each form is a content type — turn fields on or off, reorder them, then use them when creating posts.
            @else
                Content types define the fields available on posts.
            @endif
        </p>
        <a class="btn btn-primary" href="{{ route($hub.'.create') }}">{{ $isBuilder ? 'Add form' : 'Add content type' }}</a>
    </div>
    <section class="card divide-y divide-stone-100">
        @forelse ($types as $type)
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div>
                    <p class="font-medium">{{ $type->name }}</p>
                    <p class="text-xs text-slate-500">
                        /{{ $type->slug }}
                        · {{ $type->fields_count ?? $type->fields()->count() }} fields
                        · {{ $type->posts_count }} posts
                        · {{ $type->is_active ? 'Active' : 'Hidden' }}
                    </p>
                    @if ($type->description)
                        <p class="mt-1 text-sm text-slate-500">{{ $type->description }}</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <a class="btn btn-secondary" href="{{ route('site.types.show', ['siteTenant' => current_tenant()->slug, 'typeSlug' => $type->slug]) }}">View site</a>
                    <a class="btn btn-primary" href="{{ route($hub.'.edit', $type) }}">{{ $isBuilder ? 'Edit fields' : 'Fields' }}</a>
                </div>
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-slate-500">{{ $isBuilder ? 'No forms yet. Create one to start adding fields.' : 'No content types yet.' }}</p>
        @endforelse
    </section>
@endsection
