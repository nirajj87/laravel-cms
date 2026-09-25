@extends('public.layout')

@section('content')
    <p class="text-sm text-slate-500"><a href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">{{ $tenant->name }}</a></p>
    <h1 class="mt-2 text-3xl font-semibold">{{ $type->name }}</h1>
    @if ($type->description)
        <p class="mt-2 max-w-2xl text-slate-600">{{ $type->description }}</p>
    @endif
    <section class="site-grid" style="margin-top:1.5rem;">
        @forelse ($posts as $post)
            @include('public._card', ['card' => $presenter->card($post, $type, $tenant), 'cardFade' => $theme['card_fade'] ?? false])
        @empty
            <p>Nothing published in this type yet.</p>
        @endforelse
    </section>
    <div class="mt-6">{{ $posts->links() }}</div>
@endsection
