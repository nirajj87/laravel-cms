<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Notifications\ResetPasswordLink;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'status' => UserStatus::class,
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps()
            ->withPivot('tenant_id');
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLink($token));
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()
            ->withoutGlobalScope('tenant')
            ->where('roles.tenant_id', $this->tenant_id)
            ->where('roles.slug', $slug)
            ->exists();
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($slug, $this->permissionSlugs(), true);
    }

    /**
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        if ($this->isSuperAdmin()) {
            return ['*'];
        }

        return Cache::remember($this->permissionCacheKey(), 60, function () {
            return $this->roles()
                ->withoutGlobalScope('tenant')
                ->where('roles.tenant_id', $this->tenant_id)
                ->with('permissions:id,slug')
                ->get()
                ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
                ->unique()
                ->values()
                ->all();
        });
    }

    public function forgetPermissionCache(): void
    {
        Cache::forget($this->permissionCacheKey());
    }

    public function scopeForTenant($query, Tenant|int $tenant)
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $query->where('tenant_id', $tenantId)->where('is_super_admin', false);
    }

    private function permissionCacheKey(): string
    {
        return 'user.'.$this->id.'.permissions';
    }
}
