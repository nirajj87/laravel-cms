<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePostRequest;
use App\Http\Requests\Tenant\UpdatePostRequest;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Services\PostWriter;
use App\Support\CategoryContentMap;
use App\Support\SafeHtml;
use App\Support\SeoSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(private readonly PostWriter $posts) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with('contentType')
            ->when($request->integer('type'), fn ($query, $type) => $query->where('content_type_id', $type))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('q')->toString(), function ($query, $term) {
                $query->where('title', 'like', '%'.$term.'%');
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.posts.index', [
            'posts' => $posts,
            'types' => ContentType::query()->orderBy('name')->get(),
            'statuses' => PostStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Post::class);

        $categories = Category::query()->with('parent')->orderBy('sort_order')->orderBy('name')->get();
        $selectedCategory = $request->filled('category')
            ? $categories->firstWhere('id', $request->integer('category'))
            : null;

        $type = null;
        if ($selectedCategory) {
            $type = CategoryContentMap::typeFor($selectedCategory);
        } elseif ($request->filled('type')) {
            $type = ContentType::query()->with('fields')->whereKey($request->integer('type'))->first();
        }

        return view('tenant.posts.form', [
            'post' => new Post(['status' => PostStatus::Draft]),
            'types' => ContentType::query()->where('is_active', true)->orderBy('name')->get(),
            'type' => $type,
            'categories' => $categories,
            'categoryTree' => Category::tree(),
            'selectedCategory' => $selectedCategory,
            'library' => $this->library(),
            'statuses' => PostStatus::cases(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $post = $this->posts->create(
            $request->user(),
            $request->all(),
            $request->file('uploads', []) ?? [],
        );
        $this->saveSeo($post, $request);

        return redirect()->route('tenant.posts.edit', $post)->with('status', 'Post created.');
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);
        $post->load(['contentType.fields', 'values', 'categories']);
        $categories = Category::query()->with('parent')->orderBy('sort_order')->orderBy('name')->get();

        return view('tenant.posts.form', [
            'post' => $post,
            'types' => ContentType::query()->orderBy('name')->get(),
            'type' => $post->contentType,
            'categories' => $categories,
            'categoryTree' => Category::tree(),
            'selectedCategory' => $post->categories->first(),
            'library' => $this->library(),
            'statuses' => PostStatus::cases(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $post = $this->posts->update($post, $request->user(), $request->all(), $request->file('uploads', []) ?? []);
        $this->saveSeo($post, $request);

        return redirect()->route('tenant.posts.edit', $post)->with('status', 'Post saved.');
    }

    public function preview(Post $post, SiteController $site): View
    {
        $this->authorize('view', $post);

        return $site->renderPreview($post);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);
        $this->posts->delete($post);

        return redirect()->route('tenant.posts.index')->with('status', 'Post deleted.');
    }

    private function library()
    {
        return MediaAsset::query()->where('kind', 'image')->latest()->limit(40)->get();
    }

    private function saveSeo(Post $post, Request $request): void
    {
        $image = $request->integer('og_image_id') ?: null;

        if ($image && ! MediaAsset::query()->whereKey($image)->where('kind', 'image')->exists()) {
            $image = null;
        }

        $post->forceFill([
            'seo_title' => SeoSettings::text($request->input('seo_title')) ?: null,
            'seo_description' => SeoSettings::text($request->input('seo_description'), 320) ?: null,
            'seo_keywords' => SeoSettings::text($request->input('seo_keywords'), 255) ?: null,
            'canonical_url' => SafeHtml::url($request->input('canonical_url')),
            'og_image_id' => $image,
        ])->save();
    }
}
