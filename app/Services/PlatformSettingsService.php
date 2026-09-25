<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class PlatformSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return Cache::remember('platform_settings.'.$group, 300, function () use ($group) {
            return PlatformSetting::query()
                ->where('group', $group)
                ->get()
                ->mapWithKeys(fn (PlatformSetting $setting) => [$setting->key => $this->castOut($setting)])
                ->all();
        });
    }

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return $this->group($group)[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, array{label: string, type?: string, secret?: bool}>  $definitions
     */
    public function updateGroup(string $group, array $values, array $definitions): void
    {
        foreach ($definitions as $key => $definition) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $isSecret = (bool) ($definition['secret'] ?? false);
            $incoming = $values[$key];

            if ($isSecret && ($incoming === null || $incoming === '')) {
                continue;
            }

            PlatformSetting::query()->updateOrCreate(
                ['group' => $group, 'key' => $key],
                [
                    'label' => $definition['label'],
                    'type' => $definition['type'] ?? 'string',
                    'is_secret' => $isSecret,
                    'value' => $this->castIn($incoming, $definition['type'] ?? 'string', $isSecret),
                ],
            );
        }

        Cache::forget('platform_settings.'.$group);
        $this->applyRuntimeConfig();
    }

    public function applyRuntimeConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $general = $this->group('general');

        if (! empty($general['platform_name'])) {
            config(['app.name' => $general['platform_name']]);
        }

        $email = $this->group('email');

        if (($email['mailer'] ?? null) === 'smtp' && ! empty($email['host'])) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $email['host'],
                'mail.mailers.smtp.port' => (int) ($email['port'] ?? 587),
                'mail.mailers.smtp.username' => $email['username'] ?? null,
                'mail.mailers.smtp.password' => $email['password'] ?? null,
                'mail.from.address' => $email['from_address'] ?: config('mail.from.address'),
                'mail.from.name' => $email['from_name'] ?: config('app.name'),
            ]);
        }

        if (! empty($email['from_address']) && ($email['mailer'] ?? 'log') !== 'smtp') {
            config([
                'mail.from.address' => $email['from_address'],
                'mail.from.name' => $email['from_name'] ?: config('app.name'),
            ]);
        }
    }

    /**
     * @return array<string, array<string, array{label: string, type?: string, secret?: bool}>>
     */
    public function definitions(): array
    {
        return [
            'general' => [
                'platform_name' => ['label' => 'Platform name', 'type' => 'string'],
                'tagline' => ['label' => 'Tagline', 'type' => 'string'],
            ],
            'email' => [
                'mailer' => ['label' => 'Mailer', 'type' => 'string'],
                'host' => ['label' => 'SMTP host', 'type' => 'string'],
                'port' => ['label' => 'SMTP port', 'type' => 'integer'],
                'username' => ['label' => 'SMTP username', 'type' => 'string'],
                'password' => ['label' => 'SMTP password', 'type' => 'string', 'secret' => true],
                'from_address' => ['label' => 'From address', 'type' => 'string'],
                'from_name' => ['label' => 'From name', 'type' => 'string'],
            ],
            'seo' => [
                'default_title' => ['label' => 'Default title', 'type' => 'string'],
                'default_description' => ['label' => 'Default description', 'type' => 'text'],
                'default_keywords' => ['label' => 'Default keywords', 'type' => 'string'],
                'robots' => ['label' => 'Robots', 'type' => 'string'],
            ],
            'system' => [
                'support_email' => ['label' => 'Support email', 'type' => 'string'],
                'default_timezone' => ['label' => 'Default timezone', 'type' => 'string'],
                'backup_retention' => ['label' => 'Backup retention', 'type' => 'integer'],
            ],
        ];
    }

    private function castIn(mixed $value, string $type, bool $secret): ?string
    {
        if ($value === null) {
            return null;
        }

        $stored = match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            default => (string) $value,
        };

        return $secret ? Crypt::encryptString($stored) : $stored;
    }

    private function castOut(PlatformSetting $setting): mixed
    {
        $value = $setting->value;

        if ($setting->is_secret) {
            if ($value === null || $value === '') {
                return null;
            }

            try {
                $value = Crypt::decryptString($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return match ($setting->type) {
            'boolean' => $value === '1',
            'integer' => $value === null ? null : (int) $value,
            default => $value,
        };
    }

    private function tableExists(): bool
    {
        try {
            return Cache::remember('schema.has.platform_settings', 600, fn () => Schema::hasTable('platform_settings'));
        } catch (\Throwable) {
            return false;
        }
    }
}
