@extends('layouts.app')

@section('title', 'Posts')
@section('kicker', 'Content')
@section('heading', 'Posts')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a class="btn btn-secondary" href="{{ route('site.home', ['siteTenant' => current_tenant()->slug]) }}">View site</a>
        <div class="flex flex-wrap gap-2">
            @permission('posts.types')
                <a class="btn btn-secondary" href="{{ route('tenant.posts.types.index') }}">Content types</a>
            @endpermission
            @permission('posts.create')
                <a class="btn btn-primary" href="{{ route('tenant.posts.create') }}">Add post</a>
            @endpermission
        </div>
    </div>
    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4">
        <input class="field" name="q" value="{{ request('q') }}" placeholder="Search title">
        <select class="field" name="type">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected((string) request('type') === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        <select class="field" name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>
    <section class="card divide-y divide-stone-100">
        @forelse ($posts as $post)
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div>
                    <p class="font-medium">{{ $post->title }}</p>
                    <p class="text-xs text-slate-500">{{ $post->contentType?->name }} · {{ $post->status->label() }}</p>
                </div>
                <div class="flex gap-2">
                    <a class="btn btn-secondary" href="{{ route('tenant.posts.preview', $post) }}">Preview</a>
                    @permission('posts.edit')
                        <a class="btn btn-secondary" href="{{ route('tenant.posts.edit', $post) }}">Edit</a>
                    @endpermission
                </div>
            </div>
        @empty
            <p class="px-5 py-8 text-sm text-slate-500">No posts yet.</p>
        @endforelse
    </section>
    <div class="mt-4">{{ $posts->links() }}</div>
@endsection
