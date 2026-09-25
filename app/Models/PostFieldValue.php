<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostFieldValue extends Model
{
    protected $fillable = [
        'post_id',
        'field_id',
        'value',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(ContentTypeField::class, 'field_id');
    }
}
