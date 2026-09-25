<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'media_id',
        'name',
        'slug',
        'description',
        'status',
        'sort_order',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $queue = [$this->id];

        while ($queue) {
            $id = array_shift($queue);
            $children = static::query()->where('parent_id', $id)->pluck('id')->all();

            foreach ($children as $child) {
                $ids[] = (int) $child;
                $queue[] = (int) $child;
            }
        }

        return $ids;
    }

    /**
     * @return Collection<int, self>
     */
    public static function tree(): Collection
    {
        $grouped = static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (self $category) => $category->parent_id ?: 0);

        $attach = function (int $parentId) use (&$attach, $grouped): Collection {
            return ($grouped[$parentId] ?? new Collection)->map(function (self $category) use (&$attach) {
                $category->setRelation('children', $attach($category->id));

                return $category;
            })->values();
        };

        return $attach(0);
    }
}
