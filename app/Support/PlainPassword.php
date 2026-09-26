<?php

namespace App\Support;

class PlainPassword
{
    public static function alphanumeric(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $max = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < max(8, $length); $i++) {
            $out .= $chars[random_int(0, $max)];
        }

        return $out;
    }
}