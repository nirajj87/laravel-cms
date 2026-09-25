<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pages.view');
    }

    public function view(User $user, Page $page): bool
    {
        return $user->hasPermission('pages.view') && $this->same($user, $page->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pages.create');
    }

    public function update(User $user, Page $page): bool
    {
        return $user->hasPermission('pages.edit') && $this->same($user, $page->tenant_id);
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->hasPermission('pages.delete') && $this->same($user, $page->tenant_id);
    }

    private function same(User $user, ?int $tenantId): bool
    {
        $current = $user->isSuperAdmin()
            ? (current_tenant()?->id ?? session('platform_tenant_id'))
            : $user->tenant_id;

        return $current && (int) $tenantId === (int) $current;
    }
}
