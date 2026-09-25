<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'group',
        'icon',
        'route_name',
        'nav_permission',
        'sort_order',
        'is_core',
        'implemented',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'implemented' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'module', 'slug');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_module')
            ->withPivot(['enabled', 'settings'])
            ->withTimestamps();
    }

    public function routeName(): ?string
    {
        if ($this->route_name) {
            return $this->route_name;
        }

        return $this->implemented ? null : 'tenant.modules.'.$this->slug;
    }
}
