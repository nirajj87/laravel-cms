<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Tenant;
use Illuminate\Support\Facades\Session;

class Cart
{
    public static function key(Tenant $tenant): string
    {
        return 'cart.'.$tenant->id;
    }

    /**
     * @return array<int, array{post_id:int,title:string,price:float,qty:int}>
     */
    public static function items(Tenant $tenant): array
    {
        $items = Session::get(self::key($tenant), []);

        return is_array($items) ? $items : [];
    }

    public static function count(Tenant $tenant): int
    {
        return (int) array_sum(array_column(self::items($tenant), 'qty'));
    }

    public static function subtotal(Tenant $tenant): float
    {
        $total = 0.0;
        foreach (self::items($tenant) as $item) {
            $total += ((float) $item['price']) * ((int) $item['qty']);
        }

        return round($total, 2);
    }

    public static function add(Tenant $tenant, Post $post, float $price, int $qty = 1): void
    {
        $items = self::items($tenant);
        $id = (int) $post->id;
        if (isset($items[$id])) {
            $items[$id]['qty'] = (int) $items[$id]['qty'] + max(1, $qty);
        } else {
            $items[$id] = [
                'post_id' => $id,
                'title' => $post->title,
                'price' => round(max(0, $price), 2),
                'qty' => max(1, $qty),
            ];
        }
        Session::put(self::key($tenant), $items);
    }

    public static function update(Tenant $tenant, int $postId, int $qty): void
    {
        $items = self::items($tenant);
        if (! isset($items[$postId])) {
            return;
        }
        if ($qty < 1) {
            unset($items[$postId]);
        } else {
            $items[$postId]['qty'] = $qty;
        }
        Session::put(self::key($tenant), $items);
    }

    public static function remove(Tenant $tenant, int $postId): void
    {
        $items = self::items($tenant);
        unset($items[$postId]);
        Session::put(self::key($tenant), $items);
    }

    public static function clear(Tenant $tenant): void
    {
        Session::forget(self::key($tenant));
    }
}
