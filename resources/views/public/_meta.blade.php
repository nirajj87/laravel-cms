@if (! empty($seoDescription))
    <meta name="description" content="{{ $seoDescription }}">
@endif
@if (! empty($seoKeywords))
    <meta name="keywords" content="{{ $seoKeywords }}">
@endif
@if (! empty($canonical))
    <link rel="canonical" href="{{ $canonical }}">
@endif
<meta property="og:title" content="{{ $ogTitle ?? $seoTitle ?? $tenant->name }}">
@if (! empty($ogDescription) || ! empty($seoDescription))
    <meta property="og:description" content="{{ $ogDescription ?? $seoDescription }}">
@endif
<meta property="og:type" content="website">
@if (! empty($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif
<meta name="twitter:card" content="{{ $twitterCard ?? ($seo['twitter_card'] ?? 'summary') }}">
<meta name="twitter:title" content="{{ $ogTitle ?? $seoTitle ?? $tenant->name }}">
@if (! empty($noindex))
    <meta name="robots" content="noindex, nofollow">
@elseif (! empty($robots))
    <meta name="robots" content="{{ $robots }}">
@endif
@if (! empty($structuredData))
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
