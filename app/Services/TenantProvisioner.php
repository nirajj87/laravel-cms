<?php

namespace App\Services;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Events\TenantProvisioned;
use App\Models\Module;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantProvisioner
{
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly ActivityLogger $activity,
        private readonly TenantContext $context,
        private readonly ContentCatalog $catalog,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $moduleSlugs
     * @return array{tenant: Tenant, owner: User}
     */
    public function provision(array $data, array $moduleSlugs, ?UploadedFile $logo = null, ?UploadedFile $favicon = null): array
    {
        $this->modules->sync();

        $result = DB::transaction(function () use ($data, $moduleSlugs, $logo, $favicon) {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'domain' => $data['domain'] ?? null,
                'subdomain' => $data['subdomain'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? TenantStatus::Active,
                'settings' => [
                    'timezone' => config('app.timezone', 'UTC'),
                    'tagline' => null,
                    'primary_color' => '#0f766e',
                ],
            ]);

            $this->storeBranding($tenant, $logo, $favicon);
            $this->syncModules($tenant, $moduleSlugs, resetSystemRoles: true);

            $this->context->set($tenant);

            $owner = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['password'],
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);

            $ownerRole = Role::query()->where('slug', 'tenant-owner')->firstOrFail();
            $owner->roles()->attach($ownerRole->id, ['tenant_id' => $tenant->id]);
            $owner->forgetPermissionCache();

            return ['tenant' => $tenant->fresh(), 'owner' => $owner];
        });

        $this->activity->log(
            'tenant.created',
            'Created workspace '.$result['tenant']->name,
            $result['tenant'],
            ['owner_email' => $result['owner']->email],
            $result['tenant']->id,
        );

        TenantProvisioned::dispatch($result['tenant'], $result['owner']);

        return $result;
    }

    /**
     * @param  list<string>  $moduleSlugs
     */
    public function syncModules(Tenant $tenant, array $moduleSlugs, bool $resetSystemRoles = false): void
    {
        $previous = $tenant->modules()->wherePivot('enabled', true)->pluck('slug');

        $core = Module::query()->where('is_core', true)->pluck('slug')->all();
        $selected = array_values(array_unique([...$core, ...$moduleSlugs]));
        $modules = Module::query()->whereIn('slug', $selected)->get();

        $sync = [];

        foreach ($modules as $module) {
            $sync[$module->id] = ['enabled' => true];
        }

        $known = Module::query()->pluck('id', 'slug');

        foreach ($known as $slug => $id) {
            if (! isset($sync[$id])) {
                $sync[$id] = ['enabled' => false];
            }
        }

        $tenant->modules()->sync($sync);
        $tenant->forgetModuleCache();
        $tenant->unsetRelation('modules');

        $this->context->set($tenant);
        $this->syncRoleGrants($tenant, $previous->all(), $resetSystemRoles);
        $this->catalog->ensureStarterContent($tenant);

        User::query()->forTenant($tenant)->each(fn (User $user) => $user->forgetPermissionCache());
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    public function syncRolePermissions(Role $role, array $permissionSlugs): void
    {
        $tenant = $role->tenant ?? Tenant::query()->findOrFail($role->tenant_id);
        $allowed = $this->modules->permissionsForEnabledModules($tenant)->pluck('id', 'slug');

        if ($role->isOwner()) {
            $role->permissions()->sync($allowed->values()->all());
        } else {
            $ids = collect($permissionSlugs)
                ->filter(fn ($slug) => $allowed->has($slug))
                ->map(fn ($slug) => $allowed[$slug])
                ->values()
                ->all();

            $role->permissions()->sync($ids);
        }

        User::query()->forTenant($tenant)->each(fn (User $user) => $user->forgetPermissionCache());
    }

    public function storeBranding(Tenant $tenant, ?UploadedFile $logo, ?UploadedFile $favicon, bool $removeLogo = false, bool $removeFavicon = false): void
    {
        if ($removeLogo && $tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
            $tenant->logo = null;
        }

        if ($removeFavicon && $tenant->favicon) {
            Storage::disk('public')->delete($tenant->favicon);
            $tenant->favicon = null;
        }

        if ($logo) {
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }

            $tenant->logo = $logo->store('tenants/'.$tenant->id, 'public');
        }

        if ($favicon) {
            if ($tenant->favicon) {
                Storage::disk('public')->delete($tenant->favicon);
            }

            $tenant->favicon = $favicon->store('tenants/'.$tenant->id, 'public');
        }

        $tenant->save();
    }

    /**
     * @param  list<string>  $previouslyEnabled
     */
    private function syncRoleGrants(Tenant $tenant, array $previouslyEnabled, bool $resetSystemRoles): void
    {
        $templates = config('roles.templates', []);
        $enabledNow = collect($tenant->enabledModuleSlugs());
        $added = $enabledNow->diff($previouslyEnabled);

        foreach ($templates as $slug => $template) {
            $role = Role::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $slug],
                [
                    'name' => $template['name'],
                    'description' => $template['description'] ?? null,
                    'is_system' => true,
                ],
            );

            $templatePermissions = $this->modules->permissionsForTemplate($slug, $tenant);

            if ($resetSystemRoles || $role->isOwner()) {
                $role->permissions()->sync($templatePermissions->pluck('id')->all());

                continue;
            }

            $enabledIds = $this->modules->permissionsForEnabledModules($tenant)->pluck('id');
            $current = $role->permissions()->pluck('permissions.id');
            $newGrants = $templatePermissions
                ->filter(fn ($permission) => $added->contains($permission->module))
                ->pluck('id');

            $role->permissions()->sync(
                $current->merge($newGrants)->unique()->intersect($enabledIds)->values()->all()
            );
        }

        $enabledIds = $this->modules->permissionsForEnabledModules($tenant)->pluck('id');

        Role::query()->where('is_system', false)->each(function (Role $role) use ($enabledIds) {
            $current = $role->permissions()->pluck('permissions.id');
            $role->permissions()->sync($current->intersect($enabledIds)->values()->all());
        });
    }

    public function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $i = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
