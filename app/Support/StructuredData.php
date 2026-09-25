<?php

namespace App\Support;

use App\Enums\FieldType;
use App\Models\ContentType;
use App\Models\ContentTypeField;
use App\Models\Post;
use App\Models\Tenant;

class StructuredData
{
    /**
     * @return array<string, mixed>
     */
    public static function website(Tenant $tenant): array
    {
        return [
            '@type' => 'WebSite',
            'name' => $tenant->name,
            'url' => route('site.home', ['siteTenant' => $tenant->slug]),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('site.search', ['siteTenant' => $tenant->slug]).'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => SeoSettings::text($item['name'], 180),
                'item' => $item['url'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function content(Tenant $tenant, ContentType $type, Post $post): ?array
    {
        $schema = config('growth.schema_types.'.$type->slug);

        if (! is_string($schema)) {
            return null;
        }

        $description = self::fieldText($post, $type, 'short_description') ?: self::fieldText($post, $type, 'description');
        $image = self::image($post, $type);
        $url = route('site.content.show', ['siteTenant' => $tenant->slug, 'entry' => $post->slug]);
        $data = [
            '@type' => $schema,
            'name' => $post->title,
            'url' => $url,
        ];

        if ($schema === 'Article') {
            $data['headline'] = $post->title;
            $data['datePublished'] = $post->published_at?->toAtomString();
        }

        if ($description) {
            $data['description'] = $description;
        }

        if ($image) {
            $data['image'] = $image;
        }

        $author = self::fieldText($post, $type, 'author');

        if ($author && in_array($schema, ['Book', 'Article', 'CreativeWork'], true)) {
            $data['author'] = ['@type' => 'Person', 'name' => $author];
        }

        $price = self::price($post, $type);

        if ($price && in_array($schema, ['Book', 'Product', 'SoftwareApplication'], true)) {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'USD',
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
            ];
        }

        if ($schema === 'SoftwareApplication') {
            $data['applicationCategory'] = 'BusinessApplication';
        }

        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    private static function fieldText(Post $post, ContentType $type, string $key): ?string
    {
        $field = $type->fields->firstWhere('key', $key);

        if (! $field?->enabled) {
            return null;
        }

        $value = $post->valueFor($field);

        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        return SeoSettings::text($value, 500);
    }

    private static function price(Post $post, ContentType $type): ?string
    {
        $field = $type->fields->first(fn (ContentTypeField $field) => $field->enabled && in_array($field->type, [FieldType::Price, FieldType::Currency], true));

        if (! $field) {
            return null;
        }

        $value = $post->valueFor($field);

        return is_numeric($value) ? number_format((float) $value, 2, '.', '') : null;
    }

    private static function image(Post $post, ContentType $type): ?string
    {
        foreach (['thumbnail', 'logo'] as $key) {
            $field = $type->fields->firstWhere('key', $key);

            if (! $field?->enabled) {
                continue;
            }

            $value = $post->valueFor($field);

            if (! is_numeric($value)) {
                continue;
            }

            $url = SeoSettings::imageUrl((int) $value);

            if ($url) {
                return $url;
            }
        }

        return null;
    }
}
