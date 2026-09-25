<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreRoleRequest;
use App\Http\Requests\Tenant\UpdateRoleRequest;
use App\Models\Module;
use App\Models\Role;
use App\Services\ActivityLogger;
use App\Services\ModuleRegistry;
use App\Services\TenantProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly TenantProvisioner $provisioner,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('tenant.roles.index', [
            'roles' => Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('tenant.roles.form', $this->formData(new Role));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $tenant = current_tenant();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'is_system' => false,
        ]);

        $this->provisioner->syncRolePermissions($role, $request->input('permissions', []));
        $this->activity->log('role.created', 'Created role '.$role->name, $role, [], $tenant->id);

        return redirect()->route('tenant.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('tenant.roles.form', $this->formData($role));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $role->fill([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        if (! $role->is_system && $request->filled('slug')) {
            $role->slug = Str::slug($request->string('slug'));
        }

        $role->save();
        $this->provisioner->syncRolePermissions($role, $request->input('permissions', []));
        $this->activity->log('role.updated', 'Updated role '.$role->name, $role, [], $role->tenant_id);

        return redirect()->route('tenant.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            abort(403, 'System roles cannot be deleted.');
        }

        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Reassign people using this role before deleting it.']);
        }

        $name = $role->name;
        $role->permissions()->detach();
        $role->delete();
        $this->activity->log('role.deleted', 'Deleted role '.$name, null, [], current_tenant()?->id);

        return redirect()->route('tenant.roles.index')->with('status', 'Role deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Role $role): array
    {
        $tenant = current_tenant();
        $permissions = $this->modules->permissionsForEnabledModules($tenant);
        $modules = Module::query()->whereIn('slug', $permissions->pluck('module')->unique())->get()->keyBy('slug');

        return [
            'role' => $role,
            'grouped' => $permissions->groupBy('module'),
            'modules' => $modules,
            'selected' => $role->isOwner()
                ? $permissions->pluck('slug')->all()
                : ($role->exists ? $role->permissions()->pluck('slug')->all() : []),
        ];
    }
}
