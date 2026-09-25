<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'logo',
        'favicon',
        'email',
        'phone',
        'address',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_module')
            ->withPivot(['enabled', 'settings'])
            ->withTimestamps();
    }

    public function enabledModules(): BelongsToMany
    {
        return $this->modules()->wherePivot('enabled', true)->orderBy('sort_order');
    }

    /**
     * @return list<string>
     */
    public function enabledModuleSlugs(): array
    {
        return Cache::remember($this->moduleCacheKey(), 300, function () {
            $enabled = $this->modules()->wherePivot('enabled', true)->pluck('slug')->all();
            $core = Module::query()->where('is_core', true)->pluck('slug')->all();

            return array_values(array_unique([...$enabled, ...$core]));
        });
    }

    public function hasModule(string $slug): bool
    {
        return in_array($slug, $this->enabledModuleSlugs(), true);
    }

    public function forgetModuleCache(): void
    {
        Cache::forget($this->moduleCacheKey());
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon ? Storage::disk('public')->url($this->favicon) : null;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    private function moduleCacheKey(): string
    {
        return 'tenant.'.$this->id.'.modules';
    }
}
