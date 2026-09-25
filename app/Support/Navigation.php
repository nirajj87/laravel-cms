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
    private array $backendSlugs = ['users', 'roles', 'permissions', 'settings', 'backup'];

    /**
     * Flat links plus one or two dropdown groups.
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

            $link = $this->link(
                $module->name,
                $route,
                $module->icon ?: 'grid',
                str_ends_with($route, '.index')
                    ? str_replace('.index', '.*', $route)
                    : (str_ends_with($route, '.edit') ? str_replace('.edit', '.*', $route) : $route),
            );

            if (in_array($module->slug, $this->frontendSlugs, true)) {
                $frontend[] = $link;
            } elseif (in_array($module->slug, $this->backendSlugs, true)) {
                $backend[] = $link;
            } else {
                $items[] = $link;
            }
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
