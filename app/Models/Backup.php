<?php

namespace App\Models;

use App\Enums\BackupStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'scope',
        'includes_files',
        'disk',
        'path',
        'filename',
        'size',
        'status',
        'error',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BackupStatus::class,
            'includes_files' => 'boolean',
            'size' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
