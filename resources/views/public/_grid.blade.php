@if (session('status'))
    <p style="margin-bottom:1rem;">{{ session('status') }}</p>
@endif
<section class="site-grid" aria-live="polite">
    @forelse ($posts as $post)
        @include('public._card', [
            'card' => $presenter->card($post, $post->contentType, $tenant),
            'cardFade' => $theme['card_fade'] ?? false,
        ])
    @empty
        <p>Nothing matches this search.</p>
    @endforelse
</section>
@if ($posts->hasPages())
    <nav style="margin-top:1.5rem;display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;" aria-label="Pagination">
        @if (($theme['listing'] ?? 'pagination') === 'pagination')
            {{ $posts->links() }}
        @elseif ($posts->nextPageUrl())
            <a class="site-btn site-more" data-mode="{{ $theme['listing'] }}" href="{{ $posts->nextPageUrl() }}{{ str_contains($posts->nextPageUrl(), '?') ? '&' : '?' }}grid=1">{{ $theme['listing'] === 'infinite' ? 'More' : 'Load more' }}</a>
        @endif
    </nav>
@endif
@if (in_array($theme['listing'] ?? 'pagination', ['load_more', 'infinite'], true))
    <script>
        document.querySelectorAll('.site-more').forEach((link) => {
            const grid = document.querySelector('#site-grid .site-grid');
            const load = async (event) => {
                if (event) event.preventDefault();
                const response = await fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await response.text();
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                parsed.querySelectorAll('.site-card').forEach((card) => grid.appendChild(card));
                const next = parsed.querySelector('.site-more');
                if (next) link.href = next.href; else link.remove();
            };
            link.addEventListener('click', load);
            if (link.dataset.mode === 'infinite' && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                new IntersectionObserver((entries) => {
                    if (entries.some((entry) => entry.isIntersecting)) load();
                }, { rootMargin: '200px' }).observe(link);
            }
        });
    </script>
@endif
