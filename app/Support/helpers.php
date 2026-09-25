<?php

use App\Models\Tenant;
use App\Support\TenantContext;

if (! function_exists('current_tenant')) {
    function current_tenant(): ?Tenant
    {
        return app(TenantContext::class)->get();
    }
}
