<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantUserService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, password: string, status: UserStatus|string, role_id: int}  $data
     */
    public function create(Tenant $tenant, array $data, User $actor): User
    {
        $role = $this->roleFor($tenant, (int) $data['role_id']);
        $this->assertCanAssign($actor, $role);

        $user = DB::transaction(function () use ($tenant, $data, $role) {
            $user = User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'status' => $data['status'],
                'email_verified_at' => now(),
            ]);

            $user->roles()->sync([$role->id => ['tenant_id' => $tenant->id]]);

            return $user;
        });

        $user->forgetPermissionCache();

        $this->activity->log('user.created', 'Added '.$user->email, $user, ['role' => $role->slug], $tenant->id);

        return $user;
    }

    /**
     * @param  array{name: string, email: string, phone?: ?string, password?: ?string, status: UserStatus|string, role_id: int}  $data
     */
    public function update(Tenant $tenant, User $user, array $data, User $actor): User
    {
        $this->assertSameTenant($tenant, $user);
        $role = $this->roleFor($tenant, (int) $data['role_id']);
        $this->assertCanAssign($actor, $role);
        $this->assertOwnerRemains($tenant, $user, $role);

        DB::transaction(function () use ($user, $data, $role, $tenant) {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->roles()->sync([$role->id => ['tenant_id' => $tenant->id]]);
        });

        $user->forgetPermissionCache();
        $this->activity->log('user.updated', 'Updated '.$user->email, $user, ['role' => $role->slug], $tenant->id);

        return $user;
    }

    public function delete(Tenant $tenant, User $user, User $actor): void
    {
        $this->assertSameTenant($tenant, $user);

        if ($actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($user->hasRole('tenant-owner') && $this->ownerCount($tenant) <= 1) {
            throw ValidationException::withMessages([
                'user' => 'The workspace needs at least one owner.',
            ]);
        }

        $email = $user->email;
        $user->roles()->detach();
        $user->delete();

        $this->activity->log('user.deleted', 'Removed '.$email, null, ['email' => $email], $tenant->id);
    }

    private function roleFor(Tenant $tenant, int $roleId): Role
    {
        $role = Role::query()->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereKey($roleId)
            ->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role_id' => 'Choose a role from this workspace.',
            ]);
        }

        return $role;
    }

    private function assertCanAssign(User $actor, Role $role): void
    {
        if ($role->isOwner() && ! $actor->isSuperAdmin() && ! $actor->hasRole('tenant-owner')) {
            throw ValidationException::withMessages([
                'role_id' => 'Only an owner can assign the owner role.',
            ]);
        }
    }

    private function assertOwnerRemains(Tenant $tenant, User $user, Role $nextRole): void
    {
        if ($nextRole->isOwner() || ! $user->hasRole('tenant-owner')) {
            return;
        }

        if ($this->ownerCount($tenant) <= 1) {
            throw ValidationException::withMessages([
                'role_id' => 'Assign another owner before changing this role.',
            ]);
        }
    }

    private function assertSameTenant(Tenant $tenant, User $user): void
    {
        if ((int) $user->tenant_id !== (int) $tenant->id || $user->isSuperAdmin()) {
            abort(404);
        }
    }

    private function ownerCount(Tenant $tenant): int
    {
        return User::query()->forTenant($tenant)->whereHas('roles', function ($query) use ($tenant) {
            $query->withoutGlobalScope('tenant')
                ->where('roles.tenant_id', $tenant->id)
                ->where('roles.slug', 'tenant-owner');
        })->count();
    }
}
