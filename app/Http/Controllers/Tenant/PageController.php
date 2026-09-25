<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\ActivityLogger;
use App\Support\SafeHtml;
use App\Support\TenantSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(): View
    {
        $this->authorize('viewAny', Page::class);

        return view('tenant.pages.index', [
            'pages' => Page::query()->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Page::class);

        return view('tenant.pages.form', ['page' => new Page(['status' => 'draft'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Page::class);
        $data = $this->validated($request);
        $tenant = current_tenant();
        $page = Page::query()->create([
            'tenant_id' => $tenant->id,
            'title' => $data['title'],
            'slug' => TenantSlug::make('pages', $tenant->id, $data['slug'] ?: $data['title']),
            'body' => SafeHtml::clean($data['body'] ?? ''),
            'status' => $data['status'],
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ]);
        $this->activity->log('page.created', 'Created page '.$page->title, $page, [], $tenant->id);

        return redirect()->route('tenant.pages.index')->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        return view('tenant.pages.form', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);
        $data = $this->validated($request, $page);
        $page->fill([
            'title' => $data['title'],
            'slug' => TenantSlug::make('pages', (int) $page->tenant_id, $data['slug'] ?: $data['title'], $page->id),
            'body' => SafeHtml::clean($data['body'] ?? ''),
            'status' => $data['status'],
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ])->save();
        $this->activity->log('page.updated', 'Updated page '.$page->title, $page, [], $page->tenant_id);

        return redirect()->route('tenant.pages.index')->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);
        $this->activity->log('page.deleted', 'Deleted page '.$page->title, $page, [], $page->tenant_id);
        $page->delete();

        return redirect()->route('tenant.pages.index')->with('status', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['status'] === 'published' && ! $request->user()->hasPermission('pages.publish')) {
            throw ValidationException::withMessages(['status' => 'Publishing requires the pages.publish permission.']);
        }

        return $data;
    }
}
