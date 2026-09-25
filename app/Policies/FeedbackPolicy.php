<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feedback.view');
    }

    public function update(User $user, Feedback $feedback): bool
    {
        return $user->hasPermission('feedback.update') && $this->same($user, $feedback->tenant_id);
    }

    public function delete(User $user, Feedback $feedback): bool
    {
        return $user->hasPermission('feedback.delete') && $this->same($user, $feedback->tenant_id);
    }

    private function same(User $user, ?int $tenantId): bool
    {
        $current = $user->isSuperAdmin()
            ? (current_tenant()?->id ?? session('platform_tenant_id'))
            : $user->tenant_id;

        return $current && (int) $tenantId === (int) $current;
    }
}
