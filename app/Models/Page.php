<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'body',
        'status',
        'seo_title',
        'seo_description',
    ];

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
