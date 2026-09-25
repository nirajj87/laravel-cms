<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function download(User $user, Backup $backup): bool
    {
        if ($backup->path === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('backup.download')
            && $backup->scope === 'tenant'
            && (int) $backup->tenant_id === (int) $user->tenant_id;
    }

    public function delete(User $user, Backup $backup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('backup.delete')
            && $backup->scope === 'tenant'
            && (int) $backup->tenant_id === (int) $user->tenant_id;
    }
}
