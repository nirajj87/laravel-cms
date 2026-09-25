<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Models\Tenant;

class SeoSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function settings(Tenant $tenant): array
    {
        $input = $tenant->setting('seo', []) ?? [];
        $robots = (string) ($input['robots'] ?? 'index,follow');
        $card = (string) ($input['twitter_card'] ?? 'summary_large_image');
        $imageId = (int) ($input['og_image_id'] ?? 0);

        return [
            'site_title' => self::text($input['site_title'] ?? '', 180),
            'meta_description' => self::text($input['meta_description'] ?? '', 320),
            'meta_keywords' => self::text($input['meta_keywords'] ?? '', 255),
            'canonical_url' => SafeHtml::url($input['canonical_url'] ?? null) ?? '',
            'robots' => in_array($robots, config('growth.robots'), true) ? $robots : 'index,follow',
            'og_title' => self::text($input['og_title'] ?? '', 180),
            'og_description' => self::text($input['og_description'] ?? '', 320),
            'og_image_id' => $imageId > 0 ? $imageId : null,
            'twitter_card' => array_key_exists($card, config('growth.twitter_cards')) ? $card : 'summary_large_image',
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public static function meta(Tenant $tenant, array $page = []): array
    {
        $seo = self::settings($tenant);
        $title = self::text($page['title'] ?? '', 180) ?: ($seo['site_title'] ?: $tenant->name);
        $description = self::text($page['description'] ?? '', 320) ?: $seo['meta_description'];
        $canonical = SafeHtml::url($page['canonical'] ?? null);

        if (! $canonical && ! empty($page['home'])) {
            $canonical = $seo['canonical_url'] ?: url()->current();
        }

        $image = self::absolute($page['og_image'] ?? null) ?: self::imageUrl($seo['og_image_id']);

        return [
            'seoTitle' => $title,
            'seoDescription' => $description,
            'seoKeywords' => self::text($page['keywords'] ?? '', 255) ?: $seo['meta_keywords'],
            'canonical' => $canonical ?: url()->current(),
            'ogTitle' => self::text($page['og_title'] ?? '', 180) ?: ($seo['og_title'] ?: $title),
            'ogDescription' => self::text($page['og_description'] ?? '', 320) ?: ($seo['og_description'] ?: $description),
            'ogImage' => $image,
            'twitterCard' => $seo['twitter_card'],
            'robots' => $page['robots'] ?? $seo['robots'],
        ];
    }

    public static function imageUrl(?int $id): ?string
    {
        if (! $id) {
            return null;
        }

        $asset = MediaAsset::query()->whereKey($id)->where('kind', 'image')->first();

        return $asset ? self::absolute($asset->url()) : null;
    }

    public static function text(mixed $value, int $limit = 180): string
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $limit);
    }

    public static function absolute(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return SafeHtml::url($url);
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return url($url);
        }

        return null;
    }
}
