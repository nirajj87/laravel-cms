<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ContentTypeField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function hasDetailPage(): bool
    {
        $field = $this->relationLoaded('fields')
            ? $this->fields->firstWhere('key', 'detail_page')
            : $this->fields()->where('key', 'detail_page')->first();

        if (! $field) {
            return true;
        }

        return (bool) $field->enabled;
    }

    public function categoryFieldEnabled(): bool
    {
        $field = $this->relationLoaded('fields')
            ? $this->fields->firstWhere('key', 'category')
            : $this->fields()->where('key', 'category')->first();

        return (bool) $field?->enabled;
    }
}
