<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'content_type_id',
        'user_id',
        'title',
        'slug',
        'status',
        'published_at',
        'scheduled_at',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_image_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(PostFieldValue::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }

    public function inventoryItem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InventoryItem::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('status', PostStatus::Published)
                ->orWhere(function (Builder $query) {
                    $query->where('status', PostStatus::Scheduled)
                        ->whereNotNull('scheduled_at')
                        ->where('scheduled_at', '<=', now());
                });
        });
    }

    public function isVisible(): bool
    {
        if ($this->status === PostStatus::Published) {
            return true;
        }

        return $this->status === PostStatus::Scheduled
            && $this->scheduled_at !== null
            && $this->scheduled_at->lte(now());
    }

    public function valueFor(ContentTypeField $field): mixed
    {
        $row = $this->relationLoaded('values')
            ? $this->values->firstWhere('field_id', $field->id)
            : $this->values()->where('field_id', $field->id)->first();

        if (! $row || $row->value === null || $row->value === '') {
            return null;
        }

        if ($field->storesStructuredValue()) {
            return json_decode($row->value, true);
        }

        return $row->value;
    }
}
