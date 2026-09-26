<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'post_id',
        'sku',
        'quantity',
        'low_stock_at',
        'track_stock',
        'allow_backorder',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'low_stock_at' => 'integer',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function isSoldOut(): bool
    {
        if (! $this->track_stock) {
            return false;
        }

        return $this->quantity < 1 && ! $this->allow_backorder;
    }

    public function isLowStock(): bool
    {
        if (! $this->track_stock) {
            return false;
        }

        return $this->quantity > 0 && $this->quantity <= $this->low_stock_at;
    }

    public function canFulfill(int $qty): bool
    {
        if (! $this->track_stock || $this->allow_backorder) {
            return true;
        }

        return $this->quantity >= max(1, $qty);
    }
}
