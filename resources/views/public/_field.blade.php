<section>
    @unless ($item['kind'] === 'button')
        <h2 class="text-sm font-medium text-slate-500">{{ $item['label'] }}</h2>
    @endunless
    @if ($item['kind'] === 'html')
        <div class="mt-2 space-y-3 text-slate-800">{!! $item['html'] !!}</div>
    @elseif ($item['kind'] === 'image')
        <img src="{{ $item['url'] }}" alt="{{ $item['alt'] }}" class="mt-2 max-h-96 rounded-2xl object-cover">
        @if (! empty($item['caption']))
            <p class="mt-2 text-sm text-slate-500">{{ $item['caption'] }}</p>
        @endif
    @elseif ($item['kind'] === 'gallery')
        <div class="mt-2 grid gap-3 sm:grid-cols-3">
            @foreach ($item['links'] as $link)
                <img src="{{ $link['url'] }}" alt="{{ $link['alt'] }}" class="h-36 w-full rounded-xl object-cover">
            @endforeach
        </div>
    @elseif ($item['kind'] === 'video')
        <iframe class="mt-2 aspect-video w-full rounded-2xl" src="{{ $item['embed'] }}" title="{{ $item['label'] }}" allowfullscreen></iframe>
    @elseif ($item['kind'] === 'color')
        <span class="mt-2 inline-flex items-center gap-2 text-sm">
            <span class="inline-block h-6 w-6 rounded-full ring-1 ring-stone-300" style="background: {{ $item['color'] }}"></span>
            {{ $item['color'] }}
        </span>
    @elseif (in_array($item['kind'], ['link', 'file', 'button'], true))
        <a class="{{ $item['kind'] === 'button' ? 'site-btn site-btn-'.($item['style'] ?? 'primary').' mt-2' : 'mt-2 inline-block text-teal-800' }}" href="{{ $item['url'] }}" @if (($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif>{{ $item['text'] }}</a>
    @else
        <p class="mt-2 whitespace-pre-line text-slate-800">{{ $item['text'] }}</p>
    @endif
</section>
