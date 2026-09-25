<?php

namespace App\Support;

use App\Models\Category;
use App\Models\ContentType;

class CategoryContentMap
{
    /**
     * Map a category (or its root parent) to the best matching content type slug.
     */
    public static function typeSlugFor(Category $category): string
    {
        $root = $category;
        $guard = 0;

        while ($root->parent_id && $guard < 8) {
            $parent = $root->relationLoaded('parent')
                ? $root->parent
                : Category::query()->find($root->parent_id);

            if (! $parent) {
                break;
            }

            $root = $parent;
            $guard++;
        }

        return match ($root->slug) {
            'ai', 'ai-tools', 'ai-writing', 'ai-image', 'ai-coding' => 'ai-tool',
            'books', 'technology', 'education', 'religious' => 'book',
            'portfolio', 'web', 'mobile', 'software' => 'portfolio',
            default => match (true) {
                str_starts_with($root->slug, 'ai') => 'ai-tool',
                str_starts_with($root->slug, 'book') => 'book',
                str_starts_with($root->slug, 'portfolio') => 'portfolio',
                default => 'article',
            },
        };
    }

    public static function typeFor(Category $category): ?ContentType
    {
        $slug = self::typeSlugFor($category);

        return ContentType::query()
            ->with('fields')
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first()
            ?: ContentType::query()->with('fields')->where('is_active', true)->orderBy('name')->first();
    }
}
