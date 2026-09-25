@extends('public.layout')

@section('content')
    <p class="text-sm text-slate-500"><a href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">{{ $tenant->name }}</a></p>
    <h1 class="mt-2 text-3xl font-semibold">{{ $category->name }}</h1>
    @if ($category->description)
        <p class="mt-2 max-w-2xl text-slate-600">{{ $category->description }}</p>
    @endif
    @if ($category->image)
        <img src="{{ $category->image->url() }}" alt="{{ $category->image->alt ?: $category->name }}" class="mt-4 h-48 w-full max-w-md rounded-2xl object-cover">
    @endif
    @if ($children->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($children as $child)
                <a class="btn btn-secondary" href="{{ route('site.topics.show', ['siteTenant' => $tenant->slug, 'topic' => $child->slug]) }}">{{ $child->name }}</a>
            @endforeach
        </div>
    @endif
    <section class="mt-8 grid gap-4 sm:grid-cols-2">
        @forelse ($posts as $post)
            @include('public._card', ['card' => $presenter->card($post, $post->contentType, $tenant)])
        @empty
            <p class="text-sm text-slate-500">Nothing published in this category yet.</p>
        @endforelse
    </section>
    <div class="mt-6">{{ $posts->links() }}</div>
@endsection
