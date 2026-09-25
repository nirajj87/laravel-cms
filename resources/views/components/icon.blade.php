@props(['name' => 'grid', 'class' => 'h-5 w-5'])

@php
    $paths = [
        'grid' => 'M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z',
        'users' => 'M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M12.5 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM20 19v-1a3.5 3.5 0 0 0-2.5-3.35M16.5 4.6a3 3 0 0 1 0 5.8',
        'shield' => 'M12 3 5 6v6c0 4.2 2.8 7.4 7 8.5 4.2-1.1 7-4.3 7-8.5V6l-7-3z',
        'key' => 'M8 15a4 4 0 1 1 3.5-6H21v3h-2v2h-2v2h-3.2A4 4 0 0 1 8 15z',
        'folder' => 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z',
        'document' => 'M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm7 1v4h4',
        'photo' => 'M4 6h16v12H4V6zm3 8 2.5-3 2 2.5L15 9l5 6M8.5 9.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z',
        'layout' => 'M4 5h16v14H4V5zm0 4h16M10 9v10',
        'form' => 'M6 4h12v16H6V4zm3 4h6M9 12h6M9 16h4',
        'menu' => 'M4 7h16M4 12h16M4 17h10',
        'paint' => 'M12 4a8 8 0 1 0 0 16h1.2a2 2 0 0 0 1.6-3.2 1.5 1.5 0 0 1 1.2-2.4H17a3 3 0 0 0 3-3 7.5 7.5 0 0 0-8-7.4zM7.5 10.5h.01M10 7.5h.01M14 7.5h.01',
        'search' => 'M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14zm5-2 4 4',
        'chart' => 'M4 19V5M4 19h16M8 16v-5M12 16V8M16 16v-3',
        'mail' => 'M4 6h16v12H4V6zm0 0 8 7 8-7',
        'chat' => 'M5 16.5 3 20l4.2-1.2A9 9 0 1 0 5 16.5z',
        'archive' => 'M3 7h18v4H3V7zm2 4h14v8H5v-8zm5 3h4',
        'cog' => 'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM4 12h2M18 12h2M12 4v2M12 18v2M6.2 6.2l1.4 1.4M16.4 16.4l1.4 1.4M17.8 6.2l-1.4 1.4M7.6 16.4l-1.4 1.4',
    ];
    $d = $paths[$name] ?? $paths['grid'];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $d }}" />
</svg>
