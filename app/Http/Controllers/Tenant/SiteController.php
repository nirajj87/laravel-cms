<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\CategoryStatus;
use App\Enums\FeedbackStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\Feedback;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tenant;
use App\Services\SiteSearch;
use App\Support\ContentPresenter;
use App\Support\SeoSettings;
use App\Support\SiteTheme;
use App\Support\StructuredData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function __construct(private readonly SiteSearch $search) {}

    public function home(Request $request, Tenant $siteTenant): View|RedirectResponse
    {
        if ($request->filled('category') && ! $request->boolean('grid')) {
            return redirect()->route('site.categories.show', array_filter([
                'siteTenant' => $siteTenant->slug,
                'topic' => $request->string('category')->toString(),
                'q' => $request->string('q')->toString() ?: null,
                'type' => $request->string('type')->toString() ?: null,
            ]));
        }

        return $this->listing($request, $siteTenant);
    }

    public function search(Request $request, Tenant $siteTenant): View
    {
        return $this->listing($request, $siteTenant);
    }

    public function category(Request $request, Tenant $siteTenant, string $topic): View
    {
        $category = Category::query()->where('slug', $topic)->where('status', CategoryStatus::Active)->firstOrFail();
        $request->merge(['category' => $category->slug]);

        return $this->listing($request, $siteTenant, $category);
    }

    public function topic(Tenant $siteTenant, string $topic): RedirectResponse
    {
        return redirect()->route('site.categories.show', ['siteTenant' => $siteTenant->slug, 'topic' => $topic]);
    }

    public function content(Tenant $siteTenant, string $entry): View
    {
        $post = Post::query()->with(['contentType.fields', 'values', 'categories'])->where('slug', $entry)->firstOrFail();
        abort_unless($post->isVisible(), 404);
        abort_unless($post->contentType?->hasDetailPage(), 404);

        return view('public.entry', $this->entryPayload($siteTenant, $post->contentType, $post, false));
    }

    public function page(Tenant $siteTenant, string $page): View
    {
        $record = Page::query()->where('slug', $page)->where('status', 'published')->firstOrFail();

        return view('public.page', array_merge([
            'tenant' => $siteTenant,
            'page' => $record,
            'filters' => [],
            'categoryOptions' => [],
            'types' => collect(),
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@graph' => [StructuredData::breadcrumbs([
                    ['name' => $siteTenant->name, 'url' => route('site.home', ['siteTenant' => $siteTenant->slug])],
                    ['name' => $record->title, 'url' => route('site.pages.show', ['siteTenant' => $siteTenant->slug, 'page' => $record->slug])],
                ])],
            ],
        ], SeoSettings::meta($siteTenant, [
            'title' => ($record->seo_title ?: $record->title).' — '.$siteTenant->name,
            'description' => $record->seo_description,
        ])));
    }

    public function newsletter(Request $request, Tenant $siteTenant): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:180']]);
        NewsletterSubscriber::query()->firstOrCreate([
            'tenant_id' => $siteTenant->id,
            'email' => strtolower($data['email']),
        ]);

        return back()->with('status', 'You are on the list.');
    }

    public function type(Request $request, Tenant $siteTenant, string $typeSlug): View
    {
        $type = $this->findType($typeSlug);
        $request->merge(['type' => $type->slug]);

        return $this->listing($request, $siteTenant, null, $type);
    }

    public function entry(Tenant $siteTenant, string $typeSlug, string $entry): View
    {
        $type = $this->findType($typeSlug);
        $post = Post::query()
            ->with(['values', 'categories'])
            ->where('content_type_id', $type->id)
            ->where('slug', $entry)
            ->firstOrFail();

        abort_unless($post->isVisible(), 404);
        abort_unless($type->hasDetailPage(), 404);

        return view('public.entry', $this->entryPayload($siteTenant, $type, $post, false));
    }

    public function renderPreview(Post $post): View
    {
        $tenant = current_tenant();
        abort_unless($tenant && (int) $post->tenant_id === (int) $tenant->id, 404);
        $post->load(['contentType.fields', 'values', 'categories']);

        return view('public.entry', $this->entryPayload($tenant, $post->contentType, $post, true));
    }

    private function listing(Request $request, Tenant $tenant, ?Category $category = null, ?ContentType $type = null): View
    {
        $theme = SiteTheme::theme($tenant->setting('theme', []) ?? []);
        $filters = [
            'q' => $request->string('q')->toString(),
            'category' => $category?->slug ?: $request->string('category')->toString(),
            'type' => $type?->slug ?: $request->string('type')->toString(),
        ];
        $posts = $this->search->paginate($tenant, $filters, $theme['per_page']);
        $heading = $type?->name ?: ($category?->seo_title ?: $category?->name ?: $tenant->name);
        $description = $type?->description ?: ($category?->seo_description ?: $tenant->setting('tagline'));
        $view = [
            'tenant' => $tenant,
            'posts' => $posts,
            'presenter' => new ContentPresenter,
            'theme' => $theme,
            'filters' => $filters,
            'types' => ContentType::query()->where('is_active', true)->orderBy('name')->get(),
            'categoryOptions' => $this->categoryOptions(),
            'listingType' => $type,
        ];
        $view = array_merge($view, SeoSettings::meta($tenant, [
            'title' => $heading.($filters['q'] !== '' ? ' — '.$filters['q'] : '').($type || $category ? ' — '.$tenant->name : ''),
            'description' => $description,
            'keywords' => $category?->seo_keywords,
            'home' => $category === null && $type === null && $filters['q'] === '',
        ]));
        $crumbs = [['name' => $tenant->name, 'url' => route('site.home', ['siteTenant' => $tenant->slug])]];

        if ($type) {
            $crumbs[] = ['name' => $type->name, 'url' => route('site.types.show', ['siteTenant' => $tenant->slug, 'typeSlug' => $type->slug])];
        }

        if ($category) {
            $crumbs[] = ['name' => $category->name, 'url' => route('site.categories.show', ['siteTenant' => $tenant->slug, 'topic' => $category->slug])];
        }

        $graph = ($category || $type) ? [] : [StructuredData::website($tenant)];
        $graph[] = StructuredData::breadcrumbs($crumbs);
        $view['structuredData'] = ['@context' => 'https://schema.org', '@graph' => $graph];

        if ($request->boolean('grid')) {
            return view('public._grid', $view);
        }

        return view('public.home', $view);
    }

    private function findType(string $slug): ContentType
    {
        return ContentType::query()
            ->with('fields')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return list<array{slug: string, label: string}>
     */
    private function categoryOptions(): array
    {
        $options = [];
        $walk = function ($nodes, int $depth) use (&$walk, &$options): void {
            foreach ($nodes as $node) {
                if ($node->status === CategoryStatus::Active) {
                    $options[] = ['slug' => $node->slug, 'label' => str_repeat('— ', $depth).$node->name];
                    $walk($node->children, $depth + 1);
                }
            }
        };
        $walk(Category::tree(), 0);

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function entryPayload(Tenant $tenant, ContentType $type, Post $post, bool $preview): array
    {
        $post->loadMissing('ogImage');
        $excerpt = $type->fields->firstWhere('key', 'short_description');
        $description = $excerpt?->enabled ? trim(strip_tags((string) $post->valueFor($excerpt))) : null;
        $home = route('site.home', ['siteTenant' => $tenant->slug]);
        $schema = StructuredData::content($tenant, $type, $post);
        $graph = [StructuredData::breadcrumbs([
            ['name' => $tenant->name, 'url' => $home],
            ['name' => $type->name, 'url' => route('site.types.show', ['siteTenant' => $tenant->slug, 'typeSlug' => $type->slug])],
            ['name' => $post->title, 'url' => route('site.content.show', ['siteTenant' => $tenant->slug, 'entry' => $post->slug])],
        ])];

        if ($schema) {
            $graph[] = $schema;
        }

        return array_merge([
            'tenant' => $tenant,
            'type' => $type,
            'post' => $post,
            'presenter' => new ContentPresenter,
            'preview' => $preview,
            'noindex' => $preview,
            'filters' => ['q' => '', 'category' => '', 'type' => ''],
            'categoryOptions' => [],
            'types' => collect(),
            'feedbackEnabled' => $tenant->hasModule('feedback'),
            'publishedFeedback' => $tenant->hasModule('feedback')
                ? Feedback::query()->where('post_id', $post->id)->where('status', FeedbackStatus::Published)->latest()->limit(8)->get()
                : collect(),
            'structuredData' => ['@context' => 'https://schema.org', '@graph' => $graph],
        ], SeoSettings::meta($tenant, [
            'title' => ($post->seo_title ?: $post->title).' — '.$tenant->name,
            'description' => $post->seo_description ?: $description,
            'keywords' => $post->seo_keywords,
            'canonical' => $post->canonical_url,
            'og_image' => $post->ogImage?->url(),
            'robots' => $preview ? 'noindex,nofollow' : null,
        ]));
    }
}
