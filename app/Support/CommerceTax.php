<?php

namespace App\Support;

class CommerceTax
{
    public const GST_RATE = 18.0;

    /**
     * @return array{subtotal: float, tax_rate: float, tax_amount: float, total: float}
     */
    public static function fromSubtotal(float $subtotal, ?float $rate = null): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $taxRate = $rate ?? self::GST_RATE;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        return [
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 2),
        ];
    }
}
