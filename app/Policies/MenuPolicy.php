<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('menu-manager.view');
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->hasPermission('menu-manager.edit') && $this->same($user, $menu->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('menu-manager.create');
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->hasPermission('menu-manager.delete') && $this->same($user, $menu->tenant_id);
    }

    private function same(User $user, ?int $tenantId): bool
    {
        $current = $user->isSuperAdmin()
            ? (current_tenant()?->id ?? session('platform_tenant_id'))
            : $user->tenant_id;

        return $current && (int) $tenantId === (int) $current;
    }
}
