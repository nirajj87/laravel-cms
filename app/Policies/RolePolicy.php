<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.view') && $this->sameTenant($user, $role);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.edit') && $this->sameTenant($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.delete')
            && ! $role->is_system
            && $this->sameTenant($user, $role);
    }

    private function sameTenant(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            $tenantId = current_tenant()?->id ?? session('platform_tenant_id');

            return $tenantId && (int) $role->tenant_id === (int) $tenantId;
        }

        return $user->tenant_id && (int) $user->tenant_id === (int) $role->tenant_id;
    }
}
