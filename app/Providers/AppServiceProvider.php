<?php

namespace App\Providers;

use App\Enums\TenantStatus;
use App\Events\TenantProvisioned;
use App\Listeners\SendTenantOwnerWelcome;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\LayoutBlock;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformSettingsService;
use App\Support\AnalyticsSettings;
use App\Support\Navigation;
use App\Support\SeoSettings;
use App\Support\SiteTheme;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin() && ! in_array($ability, ['delete'], true)) {
                return true;
            }

            return null;
        });

        Blade::if('permission', function (string $permission): bool {
            return (bool) auth()->user()?->hasPermission($permission);
        });

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $tenant = app(TenantContext::class)->get();
            $cacheKey = 'nav.v5.'.($user?->id ?? 0).'.'.($tenant?->id ?? 0).'.'.(request()->routeIs('tenant.*') ? 't' : 'p');

            $view->with('navigation', Cache::remember($cacheKey, 120, fn () => app(Navigation::class)->for($user)));
            $view->with('currentTenant', $tenant);
        });

        View::composer(['public.layout', 'public.region'], function ($view) {
            $tenant = $view->getData()['tenant'] ?? app(TenantContext::class)->get();

            if (! $tenant instanceof Tenant) {
                return;
            }

            $requestKey = 'site.chrome.'.$tenant->id;

            if (! request()->attributes->has($requestKey)) {
                request()->attributes->set($requestKey, Cache::remember('site.chrome.'.$tenant->id.'.v2', 120, function () use ($tenant) {
                    return [
                        'theme' => SiteTheme::theme($tenant->setting('theme', []) ?? []),
                        'siteSettings' => SiteTheme::site($tenant->setting('site', []) ?? []),
                        'seo' => SeoSettings::settings($tenant),
                        'analytics' => AnalyticsSettings::settings($tenant),
                        'headerMenu' => Menu::query()->with('items')->where('location', 'header')->first(),
                        'footerMenu' => Menu::query()->with('items')->where('location', 'footer')->first(),
                        'blocks' => LayoutBlock::query()->orderBy('row')->orderBy('sort_order')->get()->groupBy('region'),
                    ];
                }));
            }

            $view->with(request()->attributes->get($requestKey));
        });

        Event::listen(TenantProvisioned::class, SendTenantOwnerWelcome::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(6)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        $this->registerRouteBindings();

        try {
            app(PlatformSettingsService::class)->applyRuntimeConfig();
        } catch (\Throwable) {
            // Settings are unavailable until the database is migrated.
        }
    }

    private function registerRouteBindings(): void
    {
        Route::bind('tenantUser', function (string $value) {
            $tenant = request()->route('tenant');
            $tenantId = $tenant instanceof Tenant ? $tenant->id : (is_numeric($tenant) ? (int) $tenant : null);
            abort_unless($tenantId, 404);

            return User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_super_admin', false)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('member', function (string $value) {
            $tenantId = auth()->user()?->tenant_id ?: session('platform_tenant_id');
            abort_unless($tenantId, 404);

            return User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_super_admin', false)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('role', function (string $value) {
            $tenantId = auth()->user()?->tenant_id ?: session('platform_tenant_id');
            abort_unless($tenantId, 404);

            return Role::query()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->whereKey($value)
                ->firstOrFail();
        });

        $tenantId = function (): ?int {
            return auth()->user()?->tenant_id ?: session('platform_tenant_id');
        };

        Route::bind('category', function (string $value) use ($tenantId) {
            $id = $tenantId();
            abort_unless($id, 404);

            return Category::withoutGlobalScope('tenant')
                ->where('tenant_id', $id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('contentType', function (string $value) use ($tenantId) {
            $id = $tenantId();
            abort_unless($id, 404);

            return ContentType::withoutGlobalScope('tenant')
                ->where('tenant_id', $id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('post', function (string $value) use ($tenantId) {
            $id = $tenantId();
            abort_unless($id, 404);

            return Post::withoutGlobalScope('tenant')
                ->where('tenant_id', $id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('mediaAsset', function (string $value) use ($tenantId) {
            $id = $tenantId();
            abort_unless($id, 404);

            return MediaAsset::withoutGlobalScope('tenant')
                ->where('tenant_id', $id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('mediaFolder', function (string $value) use ($tenantId) {
            $id = $tenantId();
            abort_unless($id, 404);

            return MediaFolder::withoutGlobalScope('tenant')
                ->where('tenant_id', $id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('menuItem', function (string $value) use ($tenantId) {
            $id = $tenantId();
            abort_unless($id, 404);

            return MenuItem::query()
                ->whereKey($value)
                ->whereHas('menu', fn ($query) => $query->withoutGlobalScope('tenant')->where('tenant_id', $id))
                ->firstOrFail();
        });

        Route::bind('siteTenant', function (string $value) {
            $tenant = Tenant::query()
                ->where('slug', $value)
                ->where('status', TenantStatus::Active)
                ->firstOrFail();

            app(TenantContext::class)->set($tenant);

            return $tenant;
        });
    }
}
