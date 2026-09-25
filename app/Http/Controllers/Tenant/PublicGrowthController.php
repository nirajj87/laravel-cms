<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\CategoryStatus;
use App\Enums\FeedbackStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendWorkspaceEmail;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tenant;
use App\Support\SeoSettings;
use App\Support\StructuredData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicGrowthController extends Controller
{
    public function sitemap(Tenant $siteTenant): Response
    {
        $urls = [route('site.home', ['siteTenant' => $siteTenant->slug])];

        Page::query()->where('status', 'published')->orderBy('id')->pluck('slug')->each(function (string $slug) use (&$urls, $siteTenant) {
            $urls[] = route('site.pages.show', ['siteTenant' => $siteTenant->slug, 'page' => $slug]);
        });

        Category::query()->where('status', CategoryStatus::Active)->orderBy('id')->pluck('slug')->each(function (string $slug) use (&$urls, $siteTenant) {
            $urls[] = route('site.categories.show', ['siteTenant' => $siteTenant->slug, 'topic' => $slug]);
        });

        Post::query()->visible()->with('contentType.fields')->orderBy('id')->get()->each(function (Post $post) use (&$urls, $siteTenant) {
            if ($post->contentType?->hasDetailPage()) {
                $urls[] = route('site.content.show', ['siteTenant' => $siteTenant->slug, 'entry' => $post->slug]);
            }
        });

        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $body .= '<url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc></url>';
        }

        return response($body.'</urlset>', 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(Tenant $siteTenant): Response
    {
        $robots = SeoSettings::settings($siteTenant)['robots'];
        $lines = ['User-agent: *'];
        $lines[] = str_contains($robots, 'noindex') ? 'Disallow: /' : 'Allow: /';
        $lines[] = 'Sitemap: '.route('site.sitemap', ['siteTenant' => $siteTenant->slug]);

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function contact(Tenant $siteTenant): View
    {
        return view('public.contact', array_merge([
            'tenant' => $siteTenant,
            'filters' => [],
            'categoryOptions' => [],
            'types' => collect(),
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@graph' => [StructuredData::breadcrumbs([
                    ['name' => $siteTenant->name, 'url' => route('site.home', ['siteTenant' => $siteTenant->slug])],
                    ['name' => 'Contact', 'url' => route('site.contact', ['siteTenant' => $siteTenant->slug])],
                ])],
            ],
        ], SeoSettings::meta($siteTenant, ['title' => 'Contact — '.$siteTenant->name])));
    }

    public function storeContact(Request $request, Tenant $siteTenant): RedirectResponse
    {
        $this->guardCaptcha($request, $siteTenant);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'company' => ['nullable', 'string', 'max:120'],
        ]);

        if (filled($request->input('company'))) {
            return back()->with('status', 'Message sent.');
        }

        $to = SeoSettings::text($siteTenant->setting('site.contact_email', $siteTenant->email), 180);

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => 'This site is not accepting messages yet.']);
        }

        SendWorkspaceEmail::dispatch($siteTenant->id, 'contact', $to, [
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
        ]);

        return back()->with('status', 'Message sent.');
    }

    public function storeFeedback(Request $request, Tenant $siteTenant): RedirectResponse
    {
        abort_unless($siteTenant->hasModule('feedback'), 404);
        $this->guardCaptcha($request, $siteTenant);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:180'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
            'post_id' => ['nullable', 'integer'],
            'company' => ['nullable', 'string', 'max:120'],
        ]);

        if (filled($request->input('company'))) {
            return back()->with('status', 'Thanks. Your feedback is waiting for review.');
        }

        $postId = null;

        if (! empty($data['post_id'])) {
            $postId = Post::query()->whereKey($data['post_id'])->value('id');
        }

        $feedback = Feedback::query()->create([
            'tenant_id' => $siteTenant->id,
            'post_id' => $postId,
            'name' => trim(strip_tags($data['name'])),
            'email' => strtolower($data['email']),
            'rating' => (int) $data['rating'],
            'comment' => trim(strip_tags($data['comment'])),
            'status' => FeedbackStatus::Pending,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        $to = $siteTenant->setting('site.contact_email', $siteTenant->email);

        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            SendWorkspaceEmail::dispatch($siteTenant->id, 'feedback', $to, [
                'name' => $feedback->name,
                'email' => $feedback->email,
                'rating' => $feedback->rating,
                'comment' => $feedback->comment,
            ]);
        }

        return back()->with('status', 'Thanks. Your feedback is waiting for review.');
    }

    private function guardCaptcha(Request $request, Tenant $tenant): void
    {
        $settings = $tenant->setting('feedback', []) ?? [];
        $enabled = filter_var($settings['captcha_enabled'] ?? false, FILTER_VALIDATE_BOOL);

        if (! $enabled) {
            return;
        }

        $provider = (string) ($settings['captcha_provider'] ?? 'none');
        $ready = $provider !== 'none' && is_string($settings['captcha_secret'] ?? null) && str_starts_with($settings['captcha_secret'], 'enc:');

        if (! $ready || ! $request->filled('captcha_token')) {
            throw ValidationException::withMessages(['captcha' => 'Complete the captcha and try again.']);
        }
    }
}
