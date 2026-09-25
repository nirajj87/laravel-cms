<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreUserRequest;
use App\Http\Requests\Tenant\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\TenantUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly TenantUserService $users) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);
        $tenant = current_tenant();

        return view('tenant.users.index', [
            'users' => User::query()->forTenant($tenant)->with('roles')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('tenant.users.form', [
            'member' => new User(['status' => UserStatus::Active]),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create(current_tenant(), $request->validated(), $request->user());

        return redirect()->route('tenant.users.index')->with('status', 'User added.');
    }

    public function edit(User $member): View
    {
        $this->authorize('update', $member);

        return view('tenant.users.form', [
            'member' => $member,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $member): RedirectResponse
    {
        $this->authorize('update', $member);
        $this->users->update(current_tenant(), $member, $request->validated(), $request->user());

        return redirect()->route('tenant.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $this->authorize('delete', $member);
        $this->users->delete(current_tenant(), $member, $request->user());

        return redirect()->route('tenant.users.index')->with('status', 'User removed.');
    }
}
