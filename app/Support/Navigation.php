<?php

namespace App\Support;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class Navigation
{
    /** @var list<string> */
    private array $frontendSlugs = ['layout-builder', 'menu-manager', 'header-footer', 'theme-settings'];

    /** @var list<string> */
    private array $backendSlugs = ['users', 'roles', 'permissions', 'commerce', 'settings', 'backup'];

    /**
     * Flat links plus dropdown groups.
     *
     * @return list<array<string, mixed>>
     */
    public function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin() && ! request()->routeIs('tenant.*')) {
            return [
                $this->link('Dashboard', 'platform.dashboard', 'grid', 'platform.dashboard'),
                $this->link('Tenants', 'platform.tenants.index', 'users', 'platform.tenants.*'),
                $this->link('Activity', 'platform.activity.index', 'chart', 'platform.activity.*'),
                $this->link('Backups', 'platform.backups.index', 'archive', 'platform.backups.*'),
                $this->link('Settings', 'platform.settings.edit', 'cog', 'platform.settings.*'),
            ];
        }

        $tenant = current_tenant() ?? $user->tenant;

        if (! $tenant) {
            return [];
        }

        $items = [];
        $frontend = [];
        $backend = [];

        foreach (Module::query()->orderBy('sort_order')->get() as $module) {
            if (! $tenant->hasModule($module->slug)) {
                continue;
            }

            if ($module->nav_permission && ! $user->hasPermission($module->nav_permission)) {
                continue;
            }

            $route = $module->routeName();

            if (! $route || ! Route::has($route)) {
                continue;
            }

            $label = $module->name;
            $active = str_ends_with($route, '.index')
                ? str_replace('.index', '.*', $route)
                : (str_ends_with($route, '.edit') ? str_replace('.edit', '.*', $route) : $route);

            // Settings stay under Backend Settings; customers/orders live under WooCommerce.
            if ($module->slug === 'commerce') {
                $label = 'Commerce settings';
                $active = 'tenant.commerce.edit';
            }

            $link = $this->link($label, $route, $module->icon ?: 'grid', $active);

            if (in_array($module->slug, $this->frontendSlugs, true)) {
                $frontend[] = $link;
            } elseif (in_array($module->slug, $this->backendSlugs, true)) {
                $backend[] = $link;
            } else {
                $items[] = $link;
            }
        }

        if ($tenant->hasModule('commerce') && $user->hasPermission('commerce.view')
            && Route::has('tenant.commerce.customers')
            && Route::has('tenant.commerce.orders')
            && Route::has('tenant.commerce.finance')
            && Route::has('tenant.commerce.inventory')
            && Route::has('tenant.commerce.payments')) {
            $items[] = $this->group('WooCommerce', 'cart', [
                $this->link('Finance', 'tenant.commerce.finance', 'chart', 'tenant.commerce.finance'),
                $this->link('Orders', 'tenant.commerce.orders', 'document', 'tenant.commerce.orders*'),
                $this->link('Payments', 'tenant.commerce.payments', 'archive', 'tenant.commerce.payments'),
                $this->link('Customers', 'tenant.commerce.customers', 'users', 'tenant.commerce.customers*'),
                $this->link('Inventory', 'tenant.commerce.inventory', 'folder', 'tenant.commerce.inventory*'),
            ]);
        }

        if ($frontend !== []) {
            $items[] = $this->group('Frontend Settings', 'paint', $frontend);
        }

        if ($backend !== []) {
            $items[] = $this->group('Backend Settings', 'cog', $backend);
        }

        return $items;
    }

    /**
     * @return array{type: string, label: string, route: string, icon: string, active: string, current: bool}
     */
    private function link(string $label, string $route, string $icon, string $active): array
    {
        return [
            'type' => 'link',
            'label' => $label,
            'route' => $route,
            'icon' => $icon,
            'active' => $active,
            'current' => request()->routeIs($active),
        ];
    }

    /**
     * @param  list<array{type: string, label: string, route: string, icon: string, active: string, current: bool}>  $children
     * @return array{type: string, label: string, icon: string, open: bool, children: list<array<string, mixed>>}
     */
    private function group(string $label, string $icon, array $children): array
    {
        $open = false;

        foreach ($children as $child) {
            if ($child['current']) {
                $open = true;
                break;
            }
        }

        return [
            'type' => 'group',
            'label' => $label,
            'icon' => $icon,
            'open' => $open,
            'children' => $children,
        ];
    }
}
