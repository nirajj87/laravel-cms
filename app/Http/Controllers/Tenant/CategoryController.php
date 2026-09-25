<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\CategoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreCategoryRequest;
use App\Http\Requests\Tenant\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Services\MediaLibrary;
use App\Support\TenantSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly MediaLibrary $media,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('tenant.categories.index', [
            'categories' => Category::tree(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('tenant.categories.form', [
            'category' => new Category(['status' => CategoryStatus::Active, 'sort_order' => 0]),
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $tenant = current_tenant();
        $category = new Category([
            'tenant_id' => $tenant->id,
            'name' => $request->validated('name'),
            'slug' => TenantSlug::make('categories', $tenant->id, $request->validated('slug')),
            'description' => $request->validated('description'),
            'parent_id' => $request->validated('parent_id'),
            'status' => $request->validated('status'),
            'sort_order' => $request->validated('sort_order') ?? 0,
            'seo_title' => $request->validated('seo_title'),
            'seo_description' => $request->validated('seo_description'),
            'seo_keywords' => $request->validated('seo_keywords'),
        ]);

        if ($request->file('image')) {
            $category->media_id = $this->media->store($request->file('image'), null, $request->user(), 'image', $category->name)->id;
        }

        $category->save();
        $this->activity->log('category.created', 'Created category '.$category->name, $category, [], $tenant->id);

        return redirect()->route('tenant.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);
        $exclude = array_merge([$category->id], $category->descendantIds());

        return view('tenant.categories.form', [
            'category' => $category->load('image'),
            'parents' => Category::query()->whereNotIn('id', $exclude)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->fill([
            'name' => $request->validated('name'),
            'slug' => TenantSlug::make('categories', (int) $category->tenant_id, $request->validated('slug'), $category->id),
            'description' => $request->validated('description'),
            'parent_id' => $request->validated('parent_id'),
            'status' => $request->validated('status'),
            'sort_order' => $request->validated('sort_order') ?? 0,
            'seo_title' => $request->validated('seo_title'),
            'seo_description' => $request->validated('seo_description'),
            'seo_keywords' => $request->validated('seo_keywords'),
        ]);

        if ($request->boolean('remove_image')) {
            $category->media_id = null;
        }

        if ($request->file('image')) {
            $category->media_id = $this->media->store($request->file('image'), null, $request->user(), 'image', $category->name)->id;
        }

        $category->save();
        $this->activity->log('category.updated', 'Updated category '.$category->name, $category, [], $category->tenant_id);

        return redirect()->route('tenant.categories.index')->with('status', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->children()->exists() || $category->posts()->exists()) {
            return back()->withErrors([
                'category' => 'Move or delete nested categories and posts before deleting this category.',
            ]);
        }

        $this->activity->log('category.deleted', 'Deleted category '.$category->name, $category, [], $category->tenant_id);
        $category->delete();

        return redirect()->route('tenant.categories.index')->with('status', 'Category deleted.');
    }
}
