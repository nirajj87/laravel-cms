<?php

namespace App\Support;

use App\Models\Tenant;

class AnalyticsSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function settings(Tenant $tenant): array
    {
        $input = $tenant->setting('analytics', []) ?? [];
        $measurement = strtoupper(trim((string) ($input['measurement_id'] ?? '')));
        $gtm = strtoupper(trim((string) ($input['gtm_id'] ?? '')));
        $pixel = trim((string) ($input['meta_pixel_id'] ?? ''));

        return [
            'enabled' => filter_var($input['enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'measurement_id' => preg_match('/^G-[A-Z0-9]{4,32}$/', $measurement) ? $measurement : '',
            'gtm_enabled' => filter_var($input['gtm_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'gtm_id' => preg_match('/^GTM-[A-Z0-9]{4,32}$/', $gtm) ? $gtm : '',
            'pixel_enabled' => filter_var($input['pixel_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'meta_pixel_id' => preg_match('/^\d{5,20}$/', $pixel) ? $pixel : '',
        ];
    }
}
