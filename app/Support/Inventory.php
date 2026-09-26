<?php

namespace App\Support;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Post;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class Inventory
{
    public static function forPost(Tenant $tenant, Post $post): InventoryItem
    {
        return InventoryItem::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'post_id' => $post->id,
            ],
            [
                'sku' => 'SKU-'.$post->id,
                'quantity' => 25,
                'low_stock_at' => 5,
                'track_stock' => true,
                'allow_backorder' => false,
            ]
        );
    }

    public static function available(Tenant $tenant, int $postId): ?InventoryItem
    {
        return InventoryItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('post_id', $postId)
            ->first();
    }

    public static function isSoldOut(Tenant $tenant, Post $post): bool
    {
        $item = self::available($tenant, (int) $post->id);
        if (! $item) {
            return false;
        }

        return $item->isSoldOut();
    }

    public static function assertAvailable(Tenant $tenant, Post $post, int $qty): void
    {
        $item = self::forPost($tenant, $post);
        if (! $item->canFulfill($qty)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qty' => $post->title.' is sold out or stock is insufficient.',
            ]);
        }
    }

    public static function decrementForOrder(Order $order): void
    {
        if ($order->payment_status !== 'paid') {
            return;
        }

        $meta = is_array($order->billing) ? $order->billing : [];
        if (! empty($meta['inventory_applied'])) {
            return;
        }

        DB::transaction(function () use ($order, &$meta) {
            foreach ($order->items as $line) {
                if (! $line->post_id) {
                    continue;
                }

                $stock = InventoryItem::query()
                    ->where('tenant_id', $order->tenant_id)
                    ->where('post_id', $line->post_id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || ! $stock->track_stock) {
                    continue;
                }

                $stock->quantity = max(0, (int) $stock->quantity - (int) $line->quantity);
                $stock->save();
            }

            $meta['inventory_applied'] = true;
            $order->billing = $meta;
            $order->save();
        });
    }
}
