<article class="site-card {{ ($cardFade ?? false) ? 'site-fade' : '' }}">
    <div class="site-card-media">
        @if ($card['href'])
            <a href="{{ $card['href'] }}" tabindex="-1" aria-hidden="true">
        @endif
        @if ($card['image'])
            <img src="{{ $card['image'] }}" alt="" loading="lazy">
        @else
            <div class="site-placeholder" aria-hidden="true"><span>{{ $card['mark'] ?? '•' }}</span></div>
        @endif
        @if ($card['href'])
            </a>
        @endif
    </div>
    <div class="site-card-body">
        @if (! empty($card['category']))
            <p class="site-kicker">{{ $card['category'] }}</p>
        @endif
        <h2>
            @if ($card['href'])
                <a href="{{ $card['href'] }}">{{ $card['title'] }}</a>
            @else
                {{ $card['title'] }}
            @endif
        </h2>
        @if ($card['excerpt'])
            <p class="site-card-excerpt">{{ $card['excerpt'] }}</p>
        @endif
        @if ($card['author'] || $card['price'] || $card['discount'] || $card['rating'])
            <div class="site-meta">
                @if ($card['author'])<span>{{ $card['author'] }}</span>@endif
                @if ($card['price'])<span class="site-meta-price">{{ $card['price'] }}</span>@endif
                @if ($card['discount'])<span class="site-meta-deal">{{ $card['discount'] }}</span>@endif
                @if ($card['rating'])<span class="site-meta-rating">{{ $card['rating'] }}</span>@endif
            </div>
        @endif
        @if ($card['button_url'])
            <a class="site-btn site-btn-{{ $card['button_style'] }}" href="{{ $card['button_url'] }}" @if ($card['button_target'] === '_blank') target="_blank" rel="noopener noreferrer" @endif>{{ $card['button_label'] }}</a>
        @elseif ($card['href'])
            <a class="site-btn" href="{{ $card['href'] }}">{{ $card['label'] }}</a>
        @endif
    </div>
</article>
