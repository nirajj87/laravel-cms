@extends('layouts.guest')

@section('full')
<style>
    .login-shell{display:grid;min-height:100vh;grid-template-columns:1fr;}
    .login-form-pane{display:flex;flex-direction:column;justify-content:center;padding:2.5rem 1rem;}
    .login-visual{
        position:relative;display:none;min-height:100vh;overflow:hidden;
        background:#020617;color:#fff;flex-direction:column;justify-content:flex-end;padding:3rem;
    }
    .login-visual canvas{position:absolute;inset:0;width:100%;height:100%;display:block;}
    .login-visual-copy{position:relative;z-index:2;max-width:28rem;}
    .login-visual-overlay{
        position:absolute;inset:0;z-index:1;pointer-events:none;
        background:radial-gradient(900px 420px at 80% -10%, rgba(15,118,110,.45), transparent 60%),
                    linear-gradient(165deg, rgba(4,47,46,.35), rgba(2,6,23,.55) 70%);
    }
    @media (min-width:768px){
        .login-shell{grid-template-columns:minmax(0,28rem) minmax(0,1fr);}
        .login-form-pane{padding:2.5rem 2rem;}
        .login-visual{display:flex;}
    }
    @media (min-width:1024px){
        .login-shell{grid-template-columns:minmax(0,34rem) minmax(0,1fr);}
        .login-form-pane{padding:2.5rem 3rem;}
    }
</style>

<div class="login-shell">
    <main class="login-form-pane">
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

    <aside class="login-visual" id="login-visual" aria-hidden="true">
        <canvas id="login-hero-canvas" width="960" height="1080"></canvas>
        <div class="login-visual-overlay"></div>
        <div class="login-visual-copy">
            <p style="margin:0;font-size:.7rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(204,251,241,.85);">Content Platform</p>
            <h2 style="margin:1rem 0 0;font-size:clamp(1.75rem,3vw,2.25rem);font-weight:600;letter-spacing:-.03em;line-height:1.15;">
                One login for platform and workspace.
            </h2>
            <p style="margin:1rem 0 0;font-size:1rem;line-height:1.6;color:rgba(204,251,241,.75);">
                Manage tenants, content forms, themes, and public sites from the same account.
            </p>
        </div>
    </aside>
</div>

<script>
(function () {
    const canvas = document.getElementById('login-hero-canvas');
    const panel = document.getElementById('login-visual');
    if (!canvas || !panel) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let usingThree = false;
    let raf = 0;

    const fitCanvas2d = () => {
        const rect = panel.getBoundingClientRect();
        const w = Math.max(1, Math.floor(rect.width));
        const h = Math.max(1, Math.floor(rect.height));
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.floor(w * dpr);
        canvas.height = Math.floor(h * dpr);
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
        return { w, h, dpr };
    };

    const startFallback = () => {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        let size = fitCanvas2d();
        const stars = Array.from({ length: 160 }, () => ({
            x: Math.random(), y: Math.random(), z: 0.25 + Math.random() * 0.75, r: 0.7 + Math.random() * 1.8
        }));
        const t0 = performance.now();

        const draw = (now) => {
            if (usingThree) return;
            const t = (now - t0) * 0.001;
            size = fitCanvas2d();
            const { w, h, dpr } = size;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            const g = ctx.createRadialGradient(w * 0.72, h * 0.18, 0, w * 0.5, h * 0.55, Math.max(w, h) * 0.8);
            g.addColorStop(0, '#134e4a');
            g.addColorStop(0.4, '#042f2e');
            g.addColorStop(1, '#020617');
            ctx.fillStyle = g;
            ctx.fillRect(0, 0, w, h);

            for (const s of stars) {
                const y = ((s.y + t * 0.025 * s.z) % 1 + 1) % 1;
                ctx.globalAlpha = 0.3 + s.z * 0.6;
                ctx.fillStyle = '#f8fafc';
                ctx.beginPath();
                ctx.arc(s.x * w, y * h, s.r * s.z, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.globalAlpha = 1;

            const cx = w * 0.55, cy = h * 0.42, radius = Math.min(w, h) * 0.18;
            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(t * 0.28);
            ctx.strokeStyle = 'rgba(94,234,212,0.65)';
            ctx.lineWidth = 1.4;
            ctx.beginPath();
            for (let i = 0; i < 6; i++) {
                const a = (i / 6) * Math.PI * 2;
                const x = Math.cos(a) * radius, y = Math.sin(a) * radius;
                i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            }
            ctx.closePath();
            ctx.stroke();
            for (let i = 0; i < 6; i++) {
                const a = (i / 6) * Math.PI * 2;
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.lineTo(Math.cos(a) * radius, Math.sin(a) * radius);
                ctx.stroke();
            }
            ctx.restore();

            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(-t * 0.4);
            ctx.strokeStyle = 'rgba(255,255,255,0.3)';
            ctx.beginPath();
            ctx.ellipse(0, 0, radius * 1.6, radius * 0.52, 0, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();

            raf = requestAnimationFrame(draw);
        };
        raf = requestAnimationFrame(draw);
        window.addEventListener('resize', () => { size = fitCanvas2d(); });
    };

    const startThree = async () => {
        const THREE = await import('https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js');
        usingThree = true;
        cancelAnimationFrame(raf);
        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(48, 1, 0.1, 100);
        camera.position.set(0, 0.35, 16);
        const count = 520;
        const positions = new Float32Array(count * 3);
        const speeds = new Float32Array(count);
        for (let i = 0; i < count; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 40;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 22;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 18;
            speeds[i] = 0.2 + Math.random() * 0.8;
        }
        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const points = new THREE.Points(geometry, new THREE.PointsMaterial({
            color: 0xffffff, size: 0.065, transparent: true, opacity: 0.75, depthAttenuation: true
        }));
        const core = new THREE.Mesh(
            new THREE.IcosahedronGeometry(3.3, 1),
            new THREE.MeshBasicMaterial({ color: 0x5eead4, wireframe: true, transparent: true, opacity: 0.5 })
        );
        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(5.8, 0.035, 12, 140),
            new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.3 })
        );
        ring.rotation.x = Math.PI / 2.35;
        scene.add(points, core, ring);

        const resize = () => {
            const rect = panel.getBoundingClientRect();
            const width = Math.max(1, Math.floor(rect.width));
            const height = Math.max(1, Math.floor(rect.height));
            renderer.setSize(width, height, false);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
        };
        const tick = (time) => {
            const t = time * 0.001;
            core.rotation.y = t * 0.22;
            core.rotation.x = t * 0.1;
            ring.rotation.z = t * 0.15;
            points.rotation.y = t * 0.04;
            const pos = geometry.attributes.position.array;
            for (let i = 0; i < count; i++) pos[i * 3 + 1] += Math.sin(t * speeds[i] + i) * 0.004;
            geometry.attributes.position.needsUpdate = true;
            renderer.render(scene, camera);
            requestAnimationFrame(tick);
        };
        resize();
        window.addEventListener('resize', resize);
        if (window.ResizeObserver) new ResizeObserver(resize).observe(panel);
        requestAnimationFrame(tick);
    };

    // Show animation immediately; upgrade to Three.js when CDN is available.
    startFallback();
    startThree().catch(() => {});
})();
</script>
@endsection