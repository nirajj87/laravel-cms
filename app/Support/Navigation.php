<?php

namespace App\Support;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class Navigation
{
    /**
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    public function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin() && ! request()->routeIs('tenant.*')) {
            return [
                ['label' => 'Dashboard', 'route' => 'platform.dashboard', 'icon' => 'grid', 'active' => 'platform.dashboard'],
                ['label' => 'Tenants', 'route' => 'platform.tenants.index', 'icon' => 'users', 'active' => 'platform.tenants.*'],
                ['label' => 'Activity', 'route' => 'platform.activity.index', 'icon' => 'chart', 'active' => 'platform.activity.*'],
                ['label' => 'Backups', 'route' => 'platform.backups.index', 'icon' => 'archive', 'active' => 'platform.backups.*'],
                ['label' => 'Settings', 'route' => 'platform.settings.edit', 'icon' => 'cog', 'active' => 'platform.settings.*'],
            ];
        }

        $tenant = current_tenant() ?? $user->tenant;

        if (! $tenant) {
            return [];
        }

        return Module::query()
            ->orderBy('sort_order')
            ->get()
            ->filter(function (Module $module) use ($tenant, $user) {
                if (! $tenant->hasModule($module->slug)) {
                    return false;
                }

                if ($module->nav_permission && ! $user->hasPermission($module->nav_permission)) {
                    return false;
                }

                $route = $module->routeName();

                return $route && Route::has($route);
            })
            ->map(fn (Module $module) => [
                'label' => $module->name,
                'route' => $module->routeName(),
                'icon' => $module->icon ?: 'grid',
                'active' => str_ends_with($module->routeName(), '.index')
                    ? str_replace('.index', '.*', $module->routeName())
                    : $module->routeName(),
            ])
            ->values()
            ->all();
    }
}
