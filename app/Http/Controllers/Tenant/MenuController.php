<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Support\SafeHtml;
use App\Support\SiteChrome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Menu::class);

        return view('tenant.menus.index', [
            'menus' => Menu::query()->with('items')->orderBy('location')->get(),
            'pages' => Page::query()->orderBy('title')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'types' => ContentType::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Menu::class);
        $menu = Menu::query()->whereKey($request->integer('menu_id'))->firstOrFail();
        $this->authorize('update', $menu);
        $data = $this->validated($request);
        $data['enabled'] = true;

        if (! $request->filled('sort_order')) {
            $data['sort_order'] = (int) $menu->items()->max('sort_order') + 1;
        }

        $menu->items()->create($data);
        SiteChrome::forget();

        return back()->with('status', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->load('menu');
        $this->authorize('update', $menuItem->menu);
        $menuItem->fill($this->validated($request))->save();
        SiteChrome::forget();

        return back()->with('status', 'Menu saved.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->load('menu');
        $this->authorize('delete', $menuItem->menu);
        $menuItem->delete();
        SiteChrome::forget();

        return back()->with('status', 'Menu item removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(['home', 'url', 'page', 'category', 'content_type', 'login', 'categories'])],
            'url' => ['nullable', 'string', 'max:500'],
            'target_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'enabled' => ['nullable', 'boolean'],
            'open_new_tab' => ['nullable', 'boolean'],
        ]);

        $data['url'] = $data['type'] === 'url' ? SafeHtml::url($data['url'] ?? null) : null;
        $data['enabled'] = $request->boolean('enabled');
        $data['open_new_tab'] = $request->boolean('open_new_tab');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($data['type'] === 'url' && ! $data['url']) {
            throw ValidationException::withMessages(['url' => 'Enter an http or https URL.']);
        }

        return $data;
    }
}
