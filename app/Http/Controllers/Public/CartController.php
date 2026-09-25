<?php

namespace App\Http\Controllers\Public;

use App\Enums\FieldType;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tenant;
use App\Support\Cart;
use App\Support\CommerceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(Tenant $siteTenant): View
    {
        abort_unless(CommerceSettings::cartEnabled($siteTenant), 404);

        return view('public.commerce.cart', [
            'tenant' => $siteTenant,
            'items' => Cart::items($siteTenant),
            'subtotal' => Cart::subtotal($siteTenant),
            'commerce' => CommerceSettings::settings($siteTenant),
        ]);
    }

    public function add(Request $request, Tenant $siteTenant): RedirectResponse
    {
        abort_unless(CommerceSettings::cartEnabled($siteTenant), 404);
        $data = $request->validate([
            'post_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $post = Post::query()->visible()->with('contentType.fields')->whereKey($data['post_id'])->firstOrFail();
        Cart::add($siteTenant, $post, self::unitPrice($post), (int) ($data['qty'] ?? 1));

        return redirect()
            ->route('site.cart.show', ['siteTenant' => $siteTenant->slug])
            ->with('status', 'Added to cart.');
    }

    public function addGet(Tenant $siteTenant, int $post): RedirectResponse
    {
        abort_unless(CommerceSettings::cartEnabled($siteTenant), 404);
        $model = Post::query()->visible()->with('contentType.fields')->whereKey($post)->firstOrFail();
        Cart::add($siteTenant, $model, self::unitPrice($model), 1);

        return redirect()
            ->route('site.cart.show', ['siteTenant' => $siteTenant->slug])
            ->with('status', 'Added to cart.');
    }

    public function update(Request $request, Tenant $siteTenant): RedirectResponse
    {
        abort_unless(CommerceSettings::cartEnabled($siteTenant), 404);
        $data = $request->validate([
            'post_id' => ['required', 'integer'],
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);
        Cart::update($siteTenant, (int) $data['post_id'], (int) $data['qty']);

        return back()->with('status', 'Cart updated.');
    }

    public function remove(Request $request, Tenant $siteTenant): RedirectResponse
    {
        abort_unless(CommerceSettings::cartEnabled($siteTenant), 404);
        $data = $request->validate(['post_id' => ['required', 'integer']]);
        Cart::remove($siteTenant, (int) $data['post_id']);

        return back()->with('status', 'Item removed.');
    }

    public static function unitPrice(Post $post): float
    {
        $type = $post->contentType;
        if (! $type) {
            return 0.0;
        }
        $priceField = $type->fields->first(
            fn ($field) => $field->enabled && in_array($field->type, [FieldType::Price, FieldType::Currency], true)
        );
        if (! $priceField) {
            return 0.0;
        }
        $raw = $post->valueFor($priceField);
        if (is_array($raw)) {
            $raw = $raw['amount'] ?? $raw['value'] ?? 0;
        }

        return round((float) preg_replace('/[^0-9.]/', '', (string) $raw), 2);
    }
}
