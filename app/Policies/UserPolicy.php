<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $subject): bool
    {
        return $user->hasPermission('users.view') && $this->sameTenant($user, $subject);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $subject): bool
    {
        return $user->hasPermission('users.edit')
            && $this->sameTenant($user, $subject)
            && ! $subject->isSuperAdmin();
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->hasPermission('users.delete')
            && $user->id !== $subject->id
            && $this->sameTenant($user, $subject)
            && ! $subject->isSuperAdmin();
    }

    private function sameTenant(User $actor, User $subject): bool
    {
        if ($actor->isSuperAdmin()) {
            $tenantId = current_tenant()?->id ?? session('platform_tenant_id');

            return $tenantId && (int) $subject->tenant_id === (int) $tenantId;
        }

        return $actor->tenant_id && (int) $actor->tenant_id === (int) $subject->tenant_id;
    }
}
