<?php

namespace App\Models;

use App\Enums\FieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentTypeField extends Model
{
    protected $fillable = [
        'content_type_id',
        'key',
        'label',
        'type',
        'enabled',
        'required',
        'is_system',
        'searchable',
        'sort_order',
        'options',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'enabled' => 'boolean',
            'required' => 'boolean',
            'is_system' => 'boolean',
            'searchable' => 'boolean',
            'sort_order' => 'integer',
            'options' => 'array',
            'settings' => 'array',
        ];
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(PostFieldValue::class, 'field_id');
    }

    public function storesStructuredValue(): bool
    {
        if (in_array($this->type, [FieldType::Button, FieldType::Gallery, FieldType::MultiSelect, FieldType::Tags], true)) {
            return true;
        }

        return $this->type === FieldType::Checkbox && filled($this->options);
    }
}
