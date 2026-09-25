<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SiteSearch
{
    public function paginate(Tenant $tenant, array $filters, int $perPage): LengthAwarePaginator
    {
        $keyword = trim((string) ($filters['q'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $type = trim((string) ($filters['type'] ?? ''));

        return Post::query()
            ->visible()
            ->with(['contentType.fields', 'values', 'categories'])
            ->when($type !== '', function ($query) use ($type) {
                $typeId = \App\Models\ContentType::query()
                    ->where('slug', $type)
                    ->where('is_active', true)
                    ->value('id');

                $query->where('content_type_id', $typeId ?: 0);
            })
            ->when($category !== '', function ($query) use ($category) {
                $match = Category::query()->where('slug', $category)->first();
                $ids = $match ? array_merge([$match->id], $match->descendantIds()) : [];
                $query->whereHas('categories', fn ($inner) => $inner->whereIn('categories.id', $ids));
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $like = '%'.$keyword.'%';
                $query->where(function ($query) use ($like) {
                    $query->where('title', 'like', $like)
                        ->orWhereHas('categories', fn ($inner) => $inner->where('name', 'like', $like))
                        ->orWhereHas('values', function ($inner) use ($like) {
                            $inner->where('value', 'like', $like)
                                ->whereHas('field', fn ($field) => $field->where('searchable', true)->where('enabled', true));
                        });
                });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
