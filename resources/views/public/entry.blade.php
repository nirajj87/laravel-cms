@extends('public.layout')

@section('content')
    <p class="text-sm text-slate-500">
        <a href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">{{ $tenant->name }}</a>
        · <a href="{{ route('site.types.show', ['siteTenant' => $tenant->slug, 'typeSlug' => $type->slug]) }}">{{ $type->name }}</a>
    </p>
    <h1 class="mt-2 text-3xl font-semibold">{{ $post->title }}</h1>
    @if ($type->categoryFieldEnabled() && $post->categories->isNotEmpty())
        <p class="mt-3 flex flex-wrap gap-2 text-sm">
            @foreach ($post->categories as $category)
                <a class="text-teal-800" href="{{ route('site.categories.show', ['siteTenant' => $tenant->slug, 'topic' => $category->slug]) }}">{{ $category->name }}</a>
            @endforeach
        </p>
    @endif
    <div class="mt-6 space-y-6">
        @foreach ($type->fields as $field)
            @php $item = $presenter->present($post, $field); @endphp
            @if ($item)
                @include('public._field', ['item' => $item])
            @endif
        @endforeach
    </div>
    @if (! empty($feedbackEnabled))
        <section class="mt-10 max-w-xl">
            <h2 class="text-xl font-semibold">Feedback</h2>
            @forelse ($publishedFeedback as $note)
                <article class="mt-4">
                    <p class="text-sm text-slate-500">{{ $note->name }} · {{ $note->rating }}/5</p>
                    <p class="mt-1">{{ $note->comment }}</p>
                </article>
            @empty
                <p class="mt-2 text-sm text-slate-500">No published feedback yet.</p>
            @endforelse
            <form method="POST" action="{{ route('site.feedback.store', ['siteTenant' => $tenant->slug]) }}" class="mt-6">
                @csrf
                <input type="hidden" name="post_id" value="{{ $post->id }}">
                <label class="mb-3 block text-sm">Name
                    <input class="site-input mt-1" name="name" value="{{ old('name') }}" required>
                </label>
                <label class="mb-3 block text-sm">Email
                    <input class="site-input mt-1" type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <fieldset class="mb-3">
                    <legend class="text-sm">Rating</legend>
                    <div class="mt-1 flex gap-3">
                        @foreach (range(1, 5) as $star)
                            <label><input type="radio" name="rating" value="{{ $star }}" @checked((int) old('rating') === $star) required> {{ $star }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="mb-3 block text-sm">Comment
                    <textarea class="site-input mt-1" name="comment" rows="4" required>{{ old('comment') }}</textarea>
                </label>
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <label>Company <input name="company" tabindex="-1" autocomplete="off"></label>
                </div>
                <button class="site-btn" type="submit">Send feedback</button>
            </form>
        </section>
    @endif
@endsection
