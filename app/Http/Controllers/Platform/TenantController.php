<?php

namespace App\Http\Controllers\Platform;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreTenantRequest;
use App\Http\Requests\Platform\UpdateTenantRequest;
use App\Models\ActivityLog;
use App\Models\Module;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use App\Services\ModuleRegistry;
use App\Services\TenantProvisioner;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantProvisioner $provisioner,
        private readonly ModuleRegistry $modules,
        private readonly ActivityLogger $activity,
        private readonly TenantContext $context,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tenant::class);

        $search = $request->string('q')->trim()->toString();

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('platform.tenants.index', [
            'tenants' => $tenants,
            'statuses' => TenantStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Tenant::class);
        $this->modules->sync();

        return view('platform.tenants.create', [
            'modules' => Module::query()->orderBy('sort_order')->get()->groupBy('group'),
            'groups' => config('modules.groups'),
            'statuses' => TenantStatus::cases(),
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $result = $this->provisioner->provision(
            $request->safe()->except(['logo', 'favicon', 'modules', 'password_confirmation']),
            $request->input('modules', []),
            $request->file('logo'),
            $request->file('favicon'),
        );

        return redirect()
            ->route('platform.tenants.show', $result['tenant'])
            ->with('status', $result['tenant']->name.' is ready. The owner can sign in.');
    }

    public function show(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);
        $this->context->set($tenant);

        return view('platform.tenants.show', [
            'tenant' => $tenant->loadCount('users'),
            'modules' => $tenant->modules()->orderBy('sort_order')->get(),
            'activity' => ActivityLog::query()->where('tenant_id', $tenant->id)->with('user:id,name')->latest()->limit(8)->get(),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        $this->authorize('update', $tenant);
        $this->modules->sync();

        $enabled = $tenant->modules()->wherePivot('enabled', true)->pluck('slug')->all();

        return view('platform.tenants.edit', [
            'tenant' => $tenant,
            'modules' => Module::query()->orderBy('sort_order')->get()->groupBy('group'),
            'groups' => config('modules.groups'),
            'statuses' => TenantStatus::cases(),
            'enabled' => $enabled,
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->fill($request->safe()->only([
            'name', 'slug', 'domain', 'subdomain', 'email', 'phone', 'address', 'status',
        ]));
        $tenant->save();

        $this->provisioner->storeBranding(
            $tenant,
            $request->file('logo'),
            $request->file('favicon'),
            $request->boolean('remove_logo'),
            $request->boolean('remove_favicon'),
        );

        $this->provisioner->syncModules($tenant, $request->input('modules', []));
        $this->activity->log('tenant.updated', 'Updated workspace '.$tenant->name, $tenant, [], $tenant->id);

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('status', 'Workspace updated.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        DB::transaction(function () use ($tenant) {
            $this->activity->log('tenant.deleted', 'Deleted workspace '.$tenant->name, null, [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'slug' => $tenant->slug,
            ]);

            $tenant->users()->each(function ($user) {
                $user->roles()->detach();
                $user->delete();
            });

            $tenant->delete();
        });

        if ((int) session('platform_tenant_id') === (int) $tenant->id) {
            session()->forget('platform_tenant_id');
        }

        return redirect()->route('platform.tenants.index')->with('status', 'Workspace deleted.');
    }

    public function enter(Tenant $tenant): RedirectResponse
    {
        $this->authorize('view', $tenant);
        session(['platform_tenant_id' => $tenant->id]);

        return redirect()->route('tenant.dashboard');
    }
}
