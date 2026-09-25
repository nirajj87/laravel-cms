<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::resolveTenantId();

            if ($tenantId) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);

                return;
            }

            $builder->whereRaw('0 = 1');
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('tenant_id')) {
                return;
            }

            $tenantId = static::resolveTenantId();

            if ($tenantId) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function resolveTenantId(): ?int
    {
        $contextId = app(TenantContext::class)->id();

        if ($contextId) {
            return $contextId;
        }

        $user = auth()->user();

        return $user?->tenant_id ? (int) $user->tenant_id : null;
    }
}
