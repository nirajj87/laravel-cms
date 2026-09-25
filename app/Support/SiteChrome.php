<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class SiteChrome
{
    public static function forget(?Tenant $tenant = null): void
    {
        $tenant ??= current_tenant();

        if ($tenant) {
            Cache::forget('site.chrome.'.$tenant->id.'.v2');
            Cache::forget('dashboard.tenant.'.$tenant->id);
        }
    }
}
