<?php

namespace App\Support;

use App\Models\Tenant;

class CommerceSettings
{
    public const GATEWAYS = ['manual', 'cod', 'razorpay', 'stripe'];

    /**
     * @return array<string, mixed>
     */
    public static function settings(Tenant $tenant): array
    {
        $input = is_array($tenant->setting('commerce', [])) ? $tenant->setting('commerce', []) : [];
        $gateway = (string) ($input['gateway'] ?? 'manual');

        if (! in_array($gateway, self::GATEWAYS, true)) {
            $gateway = 'manual';
        }

        return [
            'cart_enabled' => filter_var($input['cart_enabled'] ?? true, FILTER_VALIDATE_BOOL),
            'checkout_enabled' => filter_var($input['checkout_enabled'] ?? true, FILTER_VALIDATE_BOOL),
            'gateway' => $gateway,
            'currency' => strtoupper(mb_substr(preg_replace('/[^A-Za-z]/', '', (string) ($input['currency'] ?? 'INR')) ?: 'INR', 0, 3)),
            'razorpay_key' => mb_substr(trim((string) ($input['razorpay_key'] ?? '')), 0, 120),
            'razorpay_secret' => mb_substr(trim((string) ($input['razorpay_secret'] ?? '')), 0, 120),
            'stripe_key' => mb_substr(trim((string) ($input['stripe_key'] ?? '')), 0, 120),
            'stripe_secret' => mb_substr(trim((string) ($input['stripe_secret'] ?? '')), 0, 120),
        ];
    }

    public static function cartEnabled(Tenant $tenant): bool
    {
        return self::settings($tenant)['cart_enabled'];
    }

    public static function checkoutEnabled(Tenant $tenant): bool
    {
        return self::settings($tenant)['checkout_enabled'];
    }
}
