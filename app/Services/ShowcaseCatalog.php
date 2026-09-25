<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ContentType;
use App\Models\LayoutBlock;
use App\Models\Tenant;
use App\Models\User;
use App\Support\SiteTheme;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class ShowcaseCatalog
{
    public function __construct(
        private readonly TenantProvisioner $provisioner,
        private readonly PostWriter $posts,
        private readonly TenantContext $context,
    ) {}

    public function ensure(): void
    {
        foreach ($this->workspaces() as $workspace) {
            $tenant = Tenant::query()->where('slug', $workspace['slug'])->first();

            if (! $tenant) {
                $tenant = $this->provisioner->provision([
                    'name' => $workspace['name'],
                    'slug' => $workspace['slug'],
                    'subdomain' => $workspace['slug'],
                    'email' => $workspace['email'],
                    'status' => 'active',
                    'admin_name' => $workspace['owner'],
                    'admin_email' => $workspace['owner_email'],
                    'password' => 'password',
                ], array_keys(config('modules.catalog')))['tenant'];
            }

            $this->brand($tenant, $workspace);
            $this->context->set($tenant->fresh());
            $this->seedCategories($tenant->fresh(), $workspace['brand']);
        }

        $this->fullWidth();
        $this->context->clear();
    }

    public function fullWidth(): void
    {
        foreach (Tenant::query()->orderBy('id')->get() as $tenant) {
            $settings = $tenant->settings ?? [];
            $theme = is_array($settings['theme'] ?? null) ? $settings['theme'] : [];
            $theme['container'] = 'full';
            $settings['theme'] = SiteTheme::theme($theme);
            $tenant->settings = $settings;
            $tenant->save();
        }
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function brand(Tenant $tenant, array $workspace): void
    {
        $settings = $tenant->settings ?? [];
        $settings['tagline'] = $workspace['tagline'];
        $settings['theme'] = SiteTheme::theme(array_merge(
            is_array($settings['theme'] ?? null) ? $settings['theme'] : [],
            $workspace['theme'],
        ));
        $settings['site'] = SiteTheme::site(array_merge(
            is_array($settings['site'] ?? null) ? $settings['site'] : [],
            [
                'footer_description' => $workspace['tagline'],
                'contact_email' => $workspace['email'],
                'copyright' => '© '.now()->year.' '.$tenant->name,
            ],
        ));
        $tenant->settings = $settings;
        $tenant->save();

        $hero = LayoutBlock::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('component', 'hero')
            ->first();

        if ($hero) {
            $hero->settings = array_merge($hero->settings ?? [], ['heading' => $workspace['heading']]);
            $hero->save();
        }
    }

    private function seedCategories(Tenant $tenant, string $brand): void
    {
        $owner = User::query()->where('tenant_id', $tenant->id)->orderBy('id')->first();

        if (! $owner) {
            return;
        }

        $library = $this->library();

        foreach (Category::query()->orderBy('id')->get() as $category) {
            $attached = DB::table('category_post')->where('category_id', $category->id)->count();

            if ($attached >= 4) {
                continue;
            }

            $spec = $library[$category->slug] ?? [
                'type' => 'article',
                'items' => [
                    [$category->name.' Notes', 'A short piece filed under '.$category->name.'.', 'Essay'],
                    [$category->name.' Guide', 'A practical guide for '.$category->name.'.', 'Guide'],
                    [$category->name.' Brief', 'The latest brief from '.$category->name.'.', 'Brief'],
                    [$category->name.' Index', 'A starting index for '.$category->name.'.', 'Index'],
                ],
            ];

            $type = ContentType::query()->where('slug', $spec['type'])->first();

            if (! $type) {
                continue;
            }

            foreach (array_slice($spec['items'], $attached, 4 - $attached) as $offset => $item) {
                $index = $attached + $offset + 1;
                $this->posts->create($owner, $this->payload($type, $category, $brand, $index, $item));
            }
        }
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $item
     * @return array<string, mixed>
     */
    private function payload(ContentType $type, Category $category, string $brand, int $index, array $item): array
    {
        $slot = ($index - 1) % 4;
        $rating = ['5', '4', '5', '4'][$slot];
        $url = 'https://example.com/'.$category->slug.'/'.$index;
        $fields = match ($type->slug) {
            'ai-tool' => [
                'short_description' => $item[1],
                'pricing' => $item[2],
                'rating' => $rating,
                'website_url' => $url,
                'use_tool' => ['url' => $url, 'label' => 'Use tool'],
            ],
            'book' => [
                'author' => $item[2],
                'price' => ['18.00', '24.00', '32.00', '28.00'][$slot],
                'discount' => ['10', '15', '5', '20'][$slot],
                'rating' => $rating,
                'short_description' => $item[1],
                'description' => '<p>'.$item[1].'</p>',
                'buy_now' => ['url' => $url, 'label' => 'Buy now'],
            ],
            'portfolio' => [
                'description' => '<p>'.$item[1].'</p>',
                'technologies' => $item[2],
                'project_url' => $url,
                'client' => $brand,
                'completed_on' => ['2025-11-02', '2026-01-18', '2026-03-09', '2026-06-21'][$slot],
            ],
            default => [
                'short_description' => $item[1],
                'description' => '<p>'.$item[1].'</p>',
            ],
        };

        return [
            'content_type_id' => $type->id,
            'title' => $brand.' '.$item[0],
            'status' => 'published',
            'categories' => [$category->id],
            'fields' => $fields,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function workspaces(): array
    {
        return [
            [
                'name' => 'Meridian Works',
                'slug' => 'meridian',
                'brand' => 'Meridian',
                'owner' => 'Maya Iyer',
                'owner_email' => 'owner@meridian.test',
                'email' => 'hello@meridian.test',
                'tagline' => 'Tools, books, and studio work from one full-width desk.',
                'heading' => 'Find the next tool, book, or project.',
                'theme' => [
                    'primary' => '#1d4ed8',
                    'secondary' => '#1e3a8a',
                    'background' => '#f8fafc',
                    'text' => '#0f172a',
                    'button' => '#1d4ed8',
                    'container' => 'full',
                    'columns' => 4,
                    'per_page' => 12,
                    'radius' => '16',
                ],
            ],
            [
                'name' => 'Sable Press',
                'slug' => 'sable',
                'brand' => 'Sable',
                'owner' => 'Jonah Ellis',
                'owner_email' => 'owner@sable.test',
                'email' => 'hello@sable.test',
                'tagline' => 'A warm press for books, essays, and finished work.',
                'heading' => 'Read, browse, and open the shelf.',
                'theme' => [
                    'primary' => '#9a3412',
                    'secondary' => '#7c2d12',
                    'background' => '#fff7ed',
                    'text' => '#1c1917',
                    'button' => '#9a3412',
                    'font' => 'source-serif',
                    'container' => 'full',
                    'columns' => 4,
                    'per_page' => 12,
                    'radius' => '12',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{type: string, items: list<array{0: string, 1: string, 2: string}>}>
     */
    private function library(): array
    {
        return [
            'ai' => ['type' => 'ai-tool', 'items' => [
                ['Atlas Router', 'Sends each question to the model that fits it.', 'Team'],
                ['Northstar Chat', 'A quiet assistant for long research threads.', 'Pro'],
                ['Field Notes', 'Turns a rough note into a short brief.', 'Free'],
                ['Signal Desk', 'Writes the morning summary for one topic.', 'Usage'],
            ]],
            'ai-tools' => ['type' => 'ai-tool', 'items' => [
                ['Draft Studio', 'A writing bench for first drafts and rewrites.', 'Pro'],
                ['Outline Bench', 'Builds a clean outline from a pile of notes.', 'Free'],
                ['Rewrite Lane', 'Keeps the meaning and tightens the sentences.', 'Team'],
                ['Caption Kit', 'Writes short captions for images and clips.', 'Usage'],
            ]],
            'ai-writing' => ['type' => 'ai-tool', 'items' => [
                ['Quiet Quill', 'A slower editor for essays and letters.', 'Pro'],
                ['Paragraph Press', 'Shapes a paragraph until it reads aloud.', 'Free'],
                ['Margin Notes', 'Leaves comments the way a good editor would.', 'Team'],
                ['Voice Draft', 'Matches a house style without flattening it.', 'Usage'],
            ]],
            'ai-image' => ['type' => 'ai-tool', 'items' => [
                ['Lumen Still', 'Makes a still image from a plain description.', 'Pro'],
                ['Palette Room', 'Keeps a project on one set of colors.', 'Team'],
                ['Frame Study', 'Tries compositions before a photo shoot.', 'Free'],
                ['Soft Render', 'A gentle renderer for product mockups.', 'Usage'],
            ]],
            'ai-coding' => ['type' => 'ai-tool', 'items' => [
                ['Pair Window', 'A second pair of eyes on a small change.', 'Team'],
                ['Diff Reader', 'Explains a diff in everyday language.', 'Free'],
                ['Test Scribe', 'Drafts the test you meant to write.', 'Pro'],
                ['Patch Notes', 'Turns a changelog into something people read.', 'Usage'],
            ]],
            'books' => ['type' => 'book', 'items' => [
                ['The Open Margin', 'Essays on making room for careful work.', 'Leela Voss'],
                ['Paper Machines', 'A field guide to tools that stay out of the way.', 'A. Rahman'],
                ['A Short Press', 'How a small press ships a book.', 'Mira Shah'],
                ['Reading Weather', 'Notes on attention, paper, and time.', 'Jonah Ellis'],
            ]],
            'technology' => ['type' => 'book', 'items' => [
                ['Circuits of Care', 'Technology that leaves people room to think.', 'Leela Voss'],
                ['The Local Network', 'What a neighborhood can build for itself.', 'A. Rahman'],
                ['Tools for Thinking', 'A pocket book of useful instruments.', 'Mira Shah'],
                ['Small Systems', 'How modest software holds up for years.', 'Jonah Ellis'],
            ]],
            'education' => ['type' => 'book', 'items' => [
                ['Classroom Light', 'Lessons that start from a real question.', 'Leela Voss'],
                ['Learn by Making', 'A workbook for studios and schools.', 'A. Rahman'],
                ['The Patient Lesson', 'Teaching without rushing the room.', 'Mira Shah'],
                ['Study Hours', 'A calendar for deep, quiet practice.', 'Jonah Ellis'],
            ]],
            'religious' => ['type' => 'book', 'items' => [
                ['Evening Verses', 'Short readings for the end of the day.', 'Leela Voss'],
                ['A Quiet Practice', 'A companion for ordinary devotion.', 'A. Rahman'],
                ['The Open Door', 'Stories about hospitality and return.', 'Mira Shah'],
                ['Letters on Mercy', 'Letters written across one long winter.', 'Jonah Ellis'],
            ]],
            'portfolio' => ['type' => 'portfolio', 'items' => [
                ['Studio Archive', 'A home for finished work and the notes behind it.', 'Archive, search'],
                ['Civic Map', 'A public map of projects across the city.', 'Maps, writing'],
                ['Reading Room', 'A digital room for long-form reading.', 'Layout, type'],
                ['Harbor Index', 'An index of studios, presses, and tools.', 'Catalog, search'],
            ]],
            'web' => ['type' => 'portfolio', 'items' => [
                ['Catalog Site', 'A full-width catalog with filters and cards.', 'Laravel, CSS'],
                ['Press Homepage', 'A homepage for a small publisher.', 'Type, layout'],
                ['Member Library', 'A library members can search by shelf.', 'Search, accounts'],
                ['Event Board', 'A board for talks, launches, and workshops.', 'Calendar, cards'],
            ]],
            'mobile' => ['type' => 'portfolio', 'items' => [
                ['Field Guide App', 'A pocket guide for walks and site visits.', 'iOS, offline'],
                ['Daily Reader', 'One essay a day, set in a quiet typeface.', 'Reading, type'],
                ['Pocket Notes', 'Notes that stay attached to a project.', 'Sync, search'],
                ['Route Cards', 'Turn-by-turn cards for a city route.', 'Maps, cards'],
            ]],
            'software' => ['type' => 'portfolio', 'items' => [
                ['Desk Ledger', 'A ledger for invoices and small retainers.', 'PHP, reports'],
                ['Review Queue', 'A queue for drafts waiting on an editor.', 'Workflow'],
                ['Archive Tool', 'Packs a finished project with its sources.', 'Files, export'],
                ['Publish Kit', 'The last checks before something goes public.', 'SEO, checks'],
            ]],
        ];
    }
}
