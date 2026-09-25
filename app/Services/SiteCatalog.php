<?php

namespace App\Services;

use App\Enums\FieldType;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\ContentTypeField;
use App\Models\LayoutBlock;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Tenant;
use App\Support\SiteTheme;
use App\Support\TenantSlug;

class SiteCatalog
{
    public function ensure(Tenant $tenant): void
    {
        $this->ensureTheme($tenant);
        $this->ensurePages($tenant);
        $this->ensureMenus($tenant);
        $this->ensureBlocks($tenant);
        $this->markSearchableFields($tenant);
    }

    public function ensureTheme(Tenant $tenant): void
    {
        $settings = $tenant->settings ?? [];
        $dirty = false;

        if (! isset($settings['theme']) || ! is_array($settings['theme'])) {
            $settings['theme'] = SiteTheme::theme([]);
            $dirty = true;
        }

        if (! isset($settings['site']) || ! is_array($settings['site'])) {
            $settings['site'] = SiteTheme::site([
                'footer_description' => (string) $tenant->setting('tagline', ''),
                'contact_email' => (string) $tenant->email,
                'contact_phone' => (string) $tenant->phone,
                'contact_address' => (string) $tenant->address,
                'copyright' => '© '.now()->year.' '.$tenant->name,
            ]);
            $dirty = true;
        }

        if ($dirty) {
            $tenant->settings = $settings;
            $tenant->save();
        }
    }

    public function saveTheme(Tenant $tenant, array $theme): void
    {
        $settings = $tenant->settings ?? [];
        $settings['theme'] = SiteTheme::theme($theme);
        $tenant->settings = $settings;
        $tenant->save();
    }

    public function saveSite(Tenant $tenant, array $site): void
    {
        $settings = $tenant->settings ?? [];
        $settings['site'] = SiteTheme::site($site);
        $tenant->settings = $settings;
        $tenant->save();
    }

    private function ensurePages(Tenant $tenant): void
    {
        if (Page::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        foreach ([
            'About' => '<p>'.$tenant->name.' publishes tools, books, and ideas from one workspace.</p>',
            'Contact' => '<p>Use the details in the footer to get in touch.</p>',
        ] as $title => $body) {
            Page::query()->create([
                'tenant_id' => $tenant->id,
                'title' => $title,
                'slug' => TenantSlug::make('pages', $tenant->id, $title),
                'body' => $body,
                'status' => 'published',
                'seo_title' => $title,
            ]);
        }
    }

    private function ensureMenus(Tenant $tenant): void
    {
        if (Menu::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $header = Menu::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Header',
            'location' => 'header',
        ]);
        $footer = Menu::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Footer',
            'location' => 'footer',
        ]);

        $about = Page::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'about')->value('id');
        $contact = Page::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'contact')->value('id');
        $ai = Category::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'ai')->value('id');
        $tools = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'ai-tool')->value('id');
        $dharmic = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'dharmic-post')->value('id');
        $portfolio = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'portfolio')->value('id');

        $items = [
            ['label' => 'Home', 'type' => 'home'],
            ['label' => 'Categories', 'type' => 'categories'],
            ['label' => 'About', 'type' => 'page', 'target_id' => $about],
            ['label' => 'Dharmic', 'type' => 'content_type', 'target_id' => $dharmic],
            ['label' => 'Tools', 'type' => 'content_type', 'target_id' => $tools],
            ['label' => 'AI', 'type' => 'category', 'target_id' => $ai],
            ['label' => 'Portfolio', 'type' => 'content_type', 'target_id' => $portfolio],
            ['label' => 'Contact', 'type' => 'page', 'target_id' => $contact],
            ['label' => 'Login', 'type' => 'login'],
        ];

        foreach ($items as $index => $item) {
            if (in_array($item['type'], ['page', 'category', 'content_type'], true) && empty($item['target_id'])) {
                continue;
            }

            $header->items()->create([
                'label' => $item['label'],
                'type' => $item['type'],
                'target_id' => $item['target_id'] ?? null,
                'sort_order' => $index,
                'enabled' => true,
            ]);
        }

        $footerItems = [
            ['label' => 'Home', 'type' => 'home', 'target_id' => null],
            ['label' => 'About', 'type' => 'page', 'target_id' => $about],
            ['label' => 'Contact', 'type' => 'page', 'target_id' => $contact],
        ];

        foreach ($footerItems as $index => $item) {
            if ($item['type'] === 'page' && ! $item['target_id']) {
                continue;
            }

            $footer->items()->create([
                'label' => $item['label'],
                'type' => $item['type'],
                'target_id' => $item['target_id'],
                'sort_order' => $index,
                'enabled' => true,
            ]);
        }
    }

    private function ensureBlocks(Tenant $tenant): void
    {
        if (LayoutBlock::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        foreach (config('site.default_blocks', []) as $block) {
            LayoutBlock::query()->create([
                'tenant_id' => $tenant->id,
                ...$block,
                'enabled' => true,
            ]);
        }
    }

    private function markSearchableFields(Tenant $tenant): void
    {
        $typeIds = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->pluck('id');

        ContentTypeField::query()
            ->whereIn('content_type_id', $typeIds)
            ->whereIn('type', [
                FieldType::Text->value,
                FieldType::LongText->value,
                FieldType::RichText->value,
                FieldType::Tags->value,
                FieldType::Select->value,
            ])
            ->update(['searchable' => true]);
    }
}
