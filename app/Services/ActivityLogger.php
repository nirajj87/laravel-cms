<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    public function log(string $action, ?string $description = null, ?Model $subject = null, array $properties = [], ?int $tenantId = null): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        $tenantId ??= app(TenantContext::class)->id();

        if (! $tenantId && $subject && isset($subject->tenant_id)) {
            $tenantId = $subject->tenant_id;
        }

        ActivityLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => explode('.', $action)[0] ?: null,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $this->redact($properties) ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255) ?: null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function redact(array $properties): array
    {
        foreach ($properties as $key => $value) {
            $name = strtolower((string) $key);

            if (str_contains($name, 'password') || str_contains($name, 'secret') || str_contains($name, 'token')) {
                $properties[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $properties[$key] = $this->redact($value);
            }
        }

        return $properties;
    }
}
