<?php

namespace App\Policies;

use App\Models\ContentType;
use App\Models\User;

class ContentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user, 'view');
    }

    public function view(User $user, ContentType $contentType): bool
    {
        return $this->canManage($user, 'view') && $this->sameTenant($user, $contentType->tenant_id);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user, 'create');
    }

    public function update(User $user, ContentType $contentType): bool
    {
        return $this->canManage($user, 'edit') && $this->sameTenant($user, $contentType->tenant_id);
    }

    public function delete(User $user, ContentType $contentType): bool
    {
        return $this->canManage($user, 'delete') && $this->sameTenant($user, $contentType->tenant_id);
    }

    private function canManage(User $user, string $action): bool
    {
        if ($user->hasPermission('posts.types')) {
            return true;
        }

        return match ($action) {
            'view' => $user->hasPermission('form-builder.view')
                || $user->hasPermission('form-builder.create')
                || $user->hasPermission('form-builder.edit'),
            'create' => $user->hasPermission('form-builder.create'),
            'edit' => $user->hasPermission('form-builder.edit'),
            'delete' => $user->hasPermission('form-builder.delete'),
            default => false,
        };
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
