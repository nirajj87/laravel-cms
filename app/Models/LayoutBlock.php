<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LayoutBlock extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'region',
        'row',
        'sort_order',
        'component',
        'col_desktop',
        'col_tablet',
        'col_mobile',
        'settings',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'enabled' => 'boolean',
            'row' => 'integer',
            'sort_order' => 'integer',
            'col_desktop' => 'integer',
            'col_tablet' => 'integer',
            'col_mobile' => 'integer',
        ];
    }
}
