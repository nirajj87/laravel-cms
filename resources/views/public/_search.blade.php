<form method="GET" action="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}" class="site-search" role="search">
    <label class="sr-only" for="site-q" style="position:absolute;width:1px;height:1px;overflow:hidden;">Search</label>
    <input class="site-input" id="site-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search content, tools, books...">
    @if (! empty($filters['category']))
        <input type="hidden" name="category" value="{{ $filters['category'] }}">
    @endif
    @if (! empty($filters['type']))
        <input type="hidden" name="type" value="{{ $filters['type'] }}">
    @endif
    <button class="site-btn" type="submit">Search</button>
</form>
@if (($types ?? collect())->isNotEmpty())
    <div class="site-types">
        <a class="site-type {{ ($filters['type'] ?? '') === '' ? 'is-current' : '' }}" href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">All</a>
        @foreach ($types as $typeOption)
            <a class="site-type {{ ($filters['type'] ?? '') === $typeOption->slug ? 'is-current' : '' }}" href="{{ route('site.home', ['siteTenant' => $tenant->slug, 'type' => $typeOption->slug, 'q' => $filters['q'] ?? null]) }}">{{ $typeOption->name }}</a>
        @endforeach
    </div>
@endif
