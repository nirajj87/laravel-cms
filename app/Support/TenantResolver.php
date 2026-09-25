<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantResolver
{
    public function fromHost(Request $request): ?Tenant
    {
        $host = strtolower($request->getHost());
        $base = strtolower((string) config('tenancy.base_domain'));

        if ($base !== '' && $host === $base) {
            return null;
        }

        if ($base !== '' && str_ends_with($host, '.'.$base)) {
            $subdomain = substr($host, 0, -strlen('.'.$base));

            if ($subdomain === '' || str_contains($subdomain, '.')) {
                return null;
            }

            return Tenant::query()->where('subdomain', $subdomain)->first();
        }

        return Tenant::query()->where('domain', $host)->first();
    }
}
