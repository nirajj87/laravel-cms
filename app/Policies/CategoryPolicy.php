<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasPermission('categories.view') && $this->sameTenant($user, $category->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermission('categories.edit') && $this->sameTenant($user, $category->tenant_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermission('categories.delete') && $this->sameTenant($user, $category->tenant_id);
    }

    private function sameTenant(User $user, ?int $tenantId): bool
    {
        if ($user->isSuperAdmin()) {
            $current = current_tenant()?->id ?? session('platform_tenant_id');

            return $current && (int) $tenantId === (int) $current;
        }

        return $user->tenant_id && (int) $user->tenant_id === (int) $tenantId;
    }
}
