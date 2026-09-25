<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ModuleRegistry
{
    /**
     * @return array{modules: int, permissions: int}
     */
    public function sync(): array
    {
        $modules = 0;
        $permissions = 0;

        foreach (config('modules.catalog', []) as $slug => $definition) {
            $implemented = (bool) ($definition['implemented'] ?? false);
            $routeName = $definition['route'] ?? ($implemented ? null : 'tenant.modules.'.$slug);

            Module::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'] ?? null,
                    'group' => $definition['group'] ?? 'system',
                    'icon' => $definition['icon'] ?? null,
                    'route_name' => $routeName,
                    'nav_permission' => $definition['nav_permission'] ?? ($slug === 'dashboard' ? null : $slug.'.view'),
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_core' => (bool) ($definition['is_core'] ?? false),
                    'implemented' => $implemented,
                ],
            );
            $modules++;

            foreach ($definition['actions'] ?? [] as $action => $label) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug.'.'.$action],
                    [
                        'module' => $slug,
                        'name' => $label,
                        'description' => $definition['name'].' — '.$label,
                    ],
                );
                $permissions++;
            }
        }

        return ['modules' => $modules, 'permissions' => $permissions];
    }

    /**
     * @return list<string>
     */
    public function missingRoutePermissions(): array
    {
        $registered = Permission::query()->pluck('slug')->all();
        $used = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                    continue;
                }

                $used[] = explode(',', substr($middleware, strlen('permission:')))[0];
            }
        }

        return array_values(array_diff(array_unique($used), $registered));
    }

    public function definition(string $slug): ?array
    {
        $definition = config('modules.catalog.'.$slug);

        return is_array($definition) ? $definition : null;
    }

    public function permissionsForEnabledModules(Tenant $tenant): Collection
    {
        return Permission::query()
            ->whereIn('module', $tenant->enabledModuleSlugs())
            ->orderBy('module')
            ->orderBy('slug')
            ->get();
    }

    public function permissionsForTemplate(string $roleSlug, Tenant $tenant): Collection
    {
        $template = config('roles.templates.'.$roleSlug);
        $available = $this->permissionsForEnabledModules($tenant);

        if (! is_array($template)) {
            return collect();
        }

        if (($template['grant'] ?? null) === '*') {
            $except = $template['except_modules'] ?? [];

            return $available->reject(fn (Permission $permission) => in_array($permission->module, $except, true))->values();
        }

        $allowed = [];

        foreach ($template['modules'] ?? [] as $module => $actions) {
            foreach ($actions as $action) {
                $allowed[] = $module.'.'.$action;
            }
        }

        return $available->filter(fn (Permission $permission) => in_array($permission->slug, $allowed, true))->values();
    }
}
