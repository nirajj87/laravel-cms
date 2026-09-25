<?php

namespace App\Policies;

use App\Models\LayoutBlock;
use App\Models\User;

class LayoutBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('layout-builder.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('layout-builder.create');
    }

    public function update(User $user, LayoutBlock $block): bool
    {
        return $user->hasPermission('layout-builder.edit') && $this->same($user, $block->tenant_id);
    }

    public function delete(User $user, LayoutBlock $block): bool
    {
        return $user->hasPermission('layout-builder.delete') && $this->same($user, $block->tenant_id);
    }

    private function same(User $user, ?int $tenantId): bool
    {
        $current = $user->isSuperAdmin()
            ? (current_tenant()?->id ?? session('platform_tenant_id'))
            : $user->tenant_id;

        return $current && (int) $tenantId === (int) $current;
    }
}
