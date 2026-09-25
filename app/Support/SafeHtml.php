<?php

namespace App\Support;

class SafeHtml
{
    public static function clean(?string $html): string
    {
        $html = strip_tags((string) $html, '<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote>');
        $html = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*(["\'])\s*(?:javascript:|data:)[^"\']*\1/i', 'href="#"', $html) ?? $html;

        return trim($html);
    }

    public static function url(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, '\\')) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        return null;
    }
}
