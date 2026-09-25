<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreUserRequest;
use App\Http\Requests\Tenant\UpdateUserRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantUserService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantUserController extends Controller
{
    public function __construct(
        private readonly TenantUserService $users,
        private readonly TenantContext $context,
    ) {}

    public function index(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);
        $this->context->set($tenant);

        return view('platform.tenants.users.index', [
            'tenant' => $tenant,
            'users' => User::query()->forTenant($tenant)->with('roles')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('view', $tenant);
        $this->context->set($tenant);

        return view('platform.tenants.users.form', [
            'tenant' => $tenant,
            'member' => new User(['status' => UserStatus::Active]),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->context->set($tenant);
        $this->users->create($tenant, $request->validated(), $request->user());

        return redirect()
            ->route('platform.tenants.users.index', $tenant)
            ->with('status', 'User added.');
    }

    public function edit(Tenant $tenant, User $tenantUser): View
    {
        $this->authorize('view', $tenant);
        $this->context->set($tenant);
        abort_unless((int) $tenantUser->tenant_id === (int) $tenant->id && ! $tenantUser->isSuperAdmin(), 404);

        return view('platform.tenants.users.form', [
            'tenant' => $tenant,
            'member' => $tenantUser,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, Tenant $tenant, User $tenantUser): RedirectResponse
    {
        $this->context->set($tenant);
        $this->users->update($tenant, $tenantUser, $request->validated(), $request->user());

        return redirect()
            ->route('platform.tenants.users.index', $tenant)
            ->with('status', 'User updated.');
    }

    public function destroy(Request $request, Tenant $tenant, User $tenantUser): RedirectResponse
    {
        $this->authorize('view', $tenant);
        $this->context->set($tenant);
        $this->users->delete($tenant, $tenantUser, $request->user());

        return redirect()
            ->route('platform.tenants.users.index', $tenant)
            ->with('status', 'User removed.');
    }
}
