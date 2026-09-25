<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Crypt;

class EmailSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function settings(Tenant $tenant, bool $withSecret = false): array
    {
        $input = $tenant->setting('email', []) ?? [];
        $encryption = (string) ($input['encryption'] ?? 'tls');
        $password = self::decrypt($input['password'] ?? null);

        return [
            'host' => self::text($input['host'] ?? '', 180),
            'port' => max(1, min(65535, (int) ($input['port'] ?? 587))),
            'username' => self::text($input['username'] ?? '', 180),
            'password' => $withSecret ? $password : '',
            'password_set' => $password !== '',
            'encryption' => in_array($encryption, ['tls', 'ssl', 'none'], true) ? $encryption : 'tls',
            'from_name' => self::text($input['from_name'] ?? '', 120),
            'from_email' => filter_var($input['from_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    public static function store(array $input, array $previous = []): array
    {
        $current = self::settings(new Tenant(['settings' => ['email' => $previous]]), true);
        $incoming = trim((string) ($input['password'] ?? ''));
        $password = $incoming !== '' ? $incoming : ($current['password'] ?? '');

        $stored = self::settings(new Tenant(['settings' => ['email' => array_merge($previous, $input, ['password' => null])]]));
        unset($stored['password'], $stored['password_set']);
        $stored['password'] = $password !== '' ? 'enc:'.Crypt::encryptString($password) : null;

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function render(string $template, array $data): string
    {
        $template = strip_tags($template);

        $rendered = preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $match) use ($data): string {
            $value = $data[strtolower($match[1])] ?? '';

            return is_scalar($value) ? strip_tags((string) $value) : '';
        }, $template);

        return trim($rendered ?? '');
    }

    public static function mailerName(Tenant $tenant): string
    {
        $settings = self::settings($tenant, true);

        if ($settings['host'] === '' || $settings['from_email'] === '') {
            return (string) config('mail.default');
        }

        config([
            'mail.mailers.workspace' => [
                'transport' => 'smtp',
                'host' => $settings['host'],
                'port' => $settings['port'],
                'encryption' => $settings['encryption'] === 'none' ? null : $settings['encryption'],
                'username' => $settings['username'] ?: null,
                'password' => $settings['password'] ?: null,
                'timeout' => 10,
            ],
        ]);

        return 'workspace';
    }

    private static function decrypt(mixed $value): string
    {
        $value = (string) $value;

        if ($value === '' || ! str_starts_with($value, 'enc:')) {
            return '';
        }

        try {
            return Crypt::decryptString(substr($value, 4));
        } catch (\Throwable) {
            return '';
        }
    }

    private static function text(mixed $value, int $limit): string
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $limit);
    }
}
