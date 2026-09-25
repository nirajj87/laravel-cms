<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('posts.view');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.view') && $this->sameTenant($user, $post->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('posts.create');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.edit') && $this->sameTenant($user, $post->tenant_id);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.delete') && $this->sameTenant($user, $post->tenant_id);
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
