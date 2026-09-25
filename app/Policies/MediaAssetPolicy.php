<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('media.view');
    }

    public function view(User $user, MediaAsset $asset): bool
    {
        return $user->hasPermission('media.view') && $this->sameTenant($user, $asset->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('media.create');
    }

    public function update(User $user, MediaAsset $asset): bool
    {
        return $user->hasPermission('media.edit') && $this->sameTenant($user, $asset->tenant_id);
    }

    public function delete(User $user, MediaAsset $asset): bool
    {
        return $user->hasPermission('media.delete') && $this->sameTenant($user, $asset->tenant_id);
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
