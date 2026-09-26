@switch ($block->component)
    @case('logo')
        <a class="site-logo" href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">
            @if ($tenant->logoUrl())
                <img src="{{ $tenant->logoUrl() }}" alt="{{ $tenant->name }}">
            @else
                {{ $tenant->name }}
            @endif
        </a>
        @break
    @case('menu')
        @php
            $menu = $block->region === 'footer' ? ($footerMenu ?? null) : ($headerMenu ?? null);
            $customerAuth = auth('customer')->user();
            $commerceOn = \App\Support\CommerceSettings::cartEnabled($tenant);
        @endphp
        @if ($menu)
            <nav class="site-nav" aria-label="{{ $menu->name }}">
                @foreach ($menu->items as $item)
                    @continue(! $item->enabled)
                    @continue($item->type === 'login' && empty($siteSettings['show_login']))
                    @if ($item->type === 'login')
                        @if ($customerAuth)
                            <form method="POST" action="{{ route('site.account.logout', ['siteTenant' => $tenant->slug]) }}" style="display:inline;margin:0;">
                                @csrf
                                <button type="submit" class="site-nav-logout" style="background:none;border:0;padding:0;margin:0;font:inherit;color:inherit;cursor:pointer;">Logout</button>
                            </form>
                        @else
                            <a href="{{ $commerceOn ? route('site.account.login', ['siteTenant' => $tenant->slug]) : route('login') }}">{{ $item->label ?: 'Login' }}</a>
                        @endif
                    @else
                        <a href="{{ $item->href($tenant) }}" @if ($item->open_new_tab) target="_blank" rel="noopener noreferrer" @endif>{{ $item->label }}</a>
                    @endif
                @endforeach
                @if ($block->region === 'header' && ($siteSettings['cta_label'] ?? '') !== '' && ($siteSettings['cta_url'] ?? '') !== '')
                    <a class="site-btn" href="{{ $siteSettings['cta_url'] }}" @if (! empty($siteSettings['cta_new_tab'])) target="_blank" rel="noopener noreferrer" @endif>{{ $siteSettings['cta_label'] }}</a>
                @endif
            </nav>
        @endif
        @break
    @case('login')
        @php
            $customerAuth = auth('customer')->user();
            $commerceOn = \App\Support\CommerceSettings::cartEnabled($tenant);
        @endphp
        @if (! empty($siteSettings['show_login']))
            @if ($customerAuth)
                <form method="POST" action="{{ route('site.account.logout', ['siteTenant' => $tenant->slug]) }}" style="display:inline;margin:0;">
                    @csrf
                    <button class="site-btn site-btn-outline" type="submit">Logout</button>
                </form>
            @else
                <a class="site-btn site-btn-outline" href="{{ $commerceOn ? route('site.account.login', ['siteTenant' => $tenant->slug]) : route('login') }}">Login</a>
            @endif
        @endif
        @break
    @case('search')
        {{-- Search lives in the hero only; skip a second mid-page search. --}}
        @break
    @case('hero')
        @php $settings = $block->settings ?? []; @endphp
        <section class="site-hero">
            <canvas class="site-hero-canvas" aria-hidden="true"></canvas>
            <div class="site-hero-copy">
                <p class="site-kicker">{{ $tenant->name }}</p>
                <h1>{{ $settings['heading'] ?? 'Search content, tools, books...' }}</h1>
                @if ($tenant->setting('tagline'))
                    <p class="site-lead">{{ $tenant->setting('tagline') }}</p>
                @endif
                @if (! empty($siteSettings['show_search']))
                    @include('public._search')
                @endif
            </div>
            @once
                <script type="module">
                    const canvas = document.querySelector('.site-hero-canvas');
                    if (canvas && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    try {
                    const THREE = await import('https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js');
                    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
                    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
                    const scene = new THREE.Scene();
                    const camera = new THREE.PerspectiveCamera(48, 1, 0.1, 100);
                    camera.position.set(0, 0.4, 18);
                    const count = 520;
                    const positions = new Float32Array(count * 3);
                    const speeds = new Float32Array(count);
                    for (let i = 0; i < count; i++) {
                        positions[i * 3] = (Math.random() - 0.5) * 42;
                        positions[i * 3 + 1] = (Math.random() - 0.5) * 20;
                        positions[i * 3 + 2] = (Math.random() - 0.5) * 18;
                        speeds[i] = 0.2 + Math.random() * 0.8;
                    }
                    const geometry = new THREE.BufferGeometry();
                    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
                    const points = new THREE.Points(
                        geometry,
                        new THREE.PointsMaterial({ color: 0xffffff, size: 0.065, transparent: true, opacity: 0.7, depthAttenuation: true })
                    );
                    const core = new THREE.Mesh(
                        new THREE.IcosahedronGeometry(3.6, 1),
                        new THREE.MeshBasicMaterial({ color: 0xffffff, wireframe: true, transparent: true, opacity: 0.34 })
                    );
                    const ring = new THREE.Mesh(
                        new THREE.TorusGeometry(6.2, 0.035, 12, 120),
                        new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.22 })
                    );
                    ring.rotation.x = Math.PI / 2.4;
                    scene.add(points, core, ring);
                    const resize = () => {
                        const width = canvas.clientWidth;
                        const height = canvas.clientHeight;
                        if (!width || !height) return;
                        renderer.setSize(width, height, false);
                        camera.aspect = width / height;
                        camera.updateProjectionMatrix();
                    };
                    const tick = (time) => {
                        const t = time * 0.001;
                        core.rotation.y = t * 0.18;
                        core.rotation.x = t * 0.08;
                        ring.rotation.z = t * 0.12;
                        points.rotation.y = t * 0.035;
                        const pos = geometry.attributes.position.array;
                        for (let i = 0; i < count; i++) {
                            pos[i * 3 + 1] += Math.sin(t * speeds[i] + i) * 0.004;
                        }
                        geometry.attributes.position.needsUpdate = true;
                        renderer.render(scene, camera);
                        requestAnimationFrame(tick);
                    };
                    resize();
                    window.addEventListener('resize', resize);
                    requestAnimationFrame(tick);
                    } catch (error) {}
                    }
                </script>
            @endonce
        </section>
        @break
    @case('category_filter')
        {{-- Category filtering is covered by hero type chips + search; skip mid-page filter. --}}
        @break
    @case('content_grid')
        @if (isset($posts))
            <div id="site-grid">
                <div class="site-browse">
                    <div>
                        <p class="site-kicker">Browse</p>
                        <h2>
                            @if (! empty($listingType))
                                {{ $listingType->name }}
                            @elseif (! empty($filters['category']))
                                Category results
                            @elseif (! empty($filters['q']))
                                Search results
                            @else
                                Latest from {{ $tenant->name }}
                            @endif
                        </h2>
                    </div>
                    <p style="margin:0;color:#64748b;font-size:.92rem;">{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('item', $posts->total()) }}</p>
                </div>
                @include('public._grid')
            </div>
        @endif
        @break
    @case('description')
        @if (($siteSettings['footer_description'] ?? '') !== '')
            <p>{{ $siteSettings['footer_description'] }}</p>
        @endif
        @break
    @case('contact')
        <div>
            <strong>Contact</strong>
            @if ($siteSettings['contact_email'] ?? '')<p><a href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a></p>@endif
            @if ($siteSettings['contact_phone'] ?? '')<p>{{ $siteSettings['contact_phone'] }}</p>@endif
            @if ($siteSettings['contact_address'] ?? '')<p>{{ $siteSettings['contact_address'] }}</p>@endif
        </div>
        @break
    @case('social')
        @if (! empty($siteSettings['social']))
            <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
                @foreach ($siteSettings['social'] as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">{{ ucfirst($social['platform']) }}</a>
                @endforeach
            </div>
        @endif
        @break
    @case('newsletter')
        @if (! empty($siteSettings['newsletter']))
            <form method="POST" action="{{ route('site.newsletter.store', ['siteTenant' => $tenant->slug]) }}">
                @csrf
                <label for="newsletter-email">{{ $siteSettings['newsletter_heading'] ?: 'Newsletter' }}</label>
                <div class="site-search" style="margin-top:.4rem;">
                    <input class="site-input" id="newsletter-email" type="email" name="email" required placeholder="Email address">
                    <button class="site-btn" type="submit">Join</button>
                </div>
            </form>
        @endif
        @break
    @case('copyright')
        <p style="opacity:.75;">{{ ($siteSettings['copyright'] ?? '') !== '' ? $siteSettings['copyright'] : '© '.now()->year.' '.$tenant->name }}</p>
        @break
    @case('cta')
        @if (($siteSettings['cta_label'] ?? '') !== '' && ($siteSettings['cta_url'] ?? '') !== '')
            <a class="site-btn" href="{{ $siteSettings['cta_url'] }}" @if (! empty($siteSettings['cta_new_tab'])) target="_blank" rel="noopener noreferrer" @endif>{{ $siteSettings['cta_label'] }}</a>
        @endif
        @break
    @case('custom_text')
        @php $html = \App\Support\SafeHtml::clean(($block->settings ?? [])['body'] ?? ($block->region === 'footer' ? ($siteSettings['custom_footer'] ?? '') : ($siteSettings['custom_header'] ?? ''))); @endphp
        @if ($html !== '')
            <div>{!! $html !!}</div>
        @endif
        @break
@endswitch
