@extends('layouts.guest')

@section('full')
    <div class="grid min-h-screen lg:grid-cols-2">
        <main class="flex flex-col justify-center px-4 py-10 sm:px-8">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8">
                    <a href="{{ route('home') }}" class="text-sm font-semibold text-teal-800">
                        {{ ($hostTenant ?? null)?->name ?? config('app.name') }}
                    </a>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-800">Welcome back</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Sign in</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ ($hostTenant ?? null)?->name ? 'Continue to '.$hostTenant->name.'.' : 'Platform and workspace accounts use the same page.' }}
                    </p>

                    <form method="POST" action="{{ route('login') }}" class="mt-7">
                        @csrf
                        <x-field label="Email" name="email">
                            <input class="field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                        </x-field>
                        <x-field label="Password" name="password">
                            <input class="field" type="password" name="password" required autocomplete="current-password">
                        </x-field>
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="remember" value="1" class="rounded border-stone-300 text-teal-800" @checked(old('remember'))>
                                Remember this browser
                            </label>
                            <a class="text-sm font-medium text-teal-800 hover:underline" href="{{ route('password.request') }}">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary w-full py-2.5" type="submit">Sign in</button>
                    </form>

                    @if (app()->environment('local'))
                        <details class="mt-6 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-600">
                            <summary class="cursor-pointer font-medium text-slate-800">Local demo accounts</summary>
                            <div class="mt-3 space-y-1.5">
                                <p>Super admin: <span class="font-mono text-xs">admin@platform.test</span></p>
                                <p>Northwind: <span class="font-mono text-xs">owner@northwind.test</span></p>
                                <p>Meridian: <span class="font-mono text-xs">owner@meridian.test</span></p>
                                <p>Sable: <span class="font-mono text-xs">owner@sable.test</span></p>
                                <p class="pt-1 text-xs text-slate-500">Password for all: <span class="font-mono">password</span></p>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </main>

        <aside class="relative hidden overflow-hidden bg-slate-950 text-white lg:flex lg:flex-col lg:justify-end lg:p-12">
            <canvas id="login-hero-canvas" class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true"></canvas>
            <div class="pointer-events-none absolute inset-0"
                 style="background:
                    radial-gradient(900px 420px at 80% -10%, rgba(15,118,110,.5), transparent 60%),
                    radial-gradient(700px 360px at 10% 80%, rgba(13,148,136,.22), transparent 55%),
                    linear-gradient(165deg, rgba(4,47,46,.55), rgba(2,6,23,.72) 70%);"></div>
            <div class="relative z-10 max-w-md">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-100/80">Content Platform</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">
                    One login for platform and workspace.
                </h2>
                <p class="mt-4 text-base leading-relaxed text-teal-50/75">
                    Manage tenants, content forms, themes, and public sites from the same account.
                </p>
            </div>
            <script type="module">
                const canvas = document.getElementById('login-hero-canvas');
                if (canvas && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    try {
                        const THREE = await import('https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js');
                        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
                        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
                        const scene = new THREE.Scene();
                        const camera = new THREE.PerspectiveCamera(48, 1, 0.1, 100);
                        camera.position.set(0, 0.35, 16);
                        const count = 480;
                        const positions = new Float32Array(count * 3);
                        const speeds = new Float32Array(count);
                        for (let i = 0; i < count; i++) {
                            positions[i * 3] = (Math.random() - 0.5) * 38;
                            positions[i * 3 + 1] = (Math.random() - 0.5) * 22;
                            positions[i * 3 + 2] = (Math.random() - 0.5) * 16;
                            speeds[i] = 0.2 + Math.random() * 0.8;
                        }
                        const geometry = new THREE.BufferGeometry();
                        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
                        const points = new THREE.Points(
                            geometry,
                            new THREE.PointsMaterial({ color: 0xffffff, size: 0.06, transparent: true, opacity: 0.72, depthAttenuation: true })
                        );
                        const core = new THREE.Mesh(
                            new THREE.IcosahedronGeometry(3.2, 1),
                            new THREE.MeshBasicMaterial({ color: 0x5eead4, wireframe: true, transparent: true, opacity: 0.4 })
                        );
                        const ring = new THREE.Mesh(
                            new THREE.TorusGeometry(5.6, 0.03, 12, 120),
                            new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.25 })
                        );
                        ring.rotation.x = Math.PI / 2.35;
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
                            core.rotation.y = t * 0.2;
                            core.rotation.x = t * 0.09;
                            ring.rotation.z = t * 0.14;
                            points.rotation.y = t * 0.04;
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
                    } catch (e) {}
                }
            </script>
        </aside>
    </div>
@endsection
