<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentType;
use App\Models\ContentTypeField;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModuleRegistry;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        app(ModuleRegistry::class)->sync();
    }

    public function test_the_homepage_searches_and_category_urls_filter_cards(): void
    {
        $workspace = $this->provision('Northwind', 'northwind', 'owner@northwind.test');
        $tenant = $workspace['tenant'];
        $ai = Category::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'ai')->firstOrFail();
        $technology = Category::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'technology')->firstOrFail();
        $tool = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'ai-tool')->firstOrFail();
        $book = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'book')->firstOrFail();
        ContentTypeField::query()->where('content_type_id', $book->id)->where('key', 'price')->update(['enabled' => false]);

        $this->actingAs($workspace['owner'])->post(route('tenant.posts.store'), [
            'content_type_id' => $tool->id,
            'title' => 'ChatGPT',
            'status' => 'published',
            'categories' => [$ai->id],
            'fields' => [
                'short_description' => 'A writing assistant',
                'use_tool' => ['url' => 'https://example.com/chatgpt', 'label' => 'Use Tool'],
            ],
        ])->assertRedirect();

        $this->actingAs($workspace['owner'])->post(route('tenant.posts.store'), [
            'content_type_id' => $book->id,
            'title' => 'Quiet Book',
            'status' => 'published',
            'categories' => [$technology->id],
            'fields' => [
                'author' => 'Ada Lovelace',
                'price' => '24.00',
                'short_description' => 'A paper book',
            ],
        ])->assertRedirect();

        $home = route('site.home', ['siteTenant' => $tenant->slug]);

        $this->get($home)
            ->assertOk()
            ->assertSee('Search content, tools, books...')
            ->assertSee('Login')
            ->assertSee('ChatGPT')
            ->assertSee('Quiet Book');

        $this->get($home.'?q=ChatGPT')
            ->assertOk()
            ->assertSee('ChatGPT')
            ->assertDontSee('Quiet Book');

        $this->get(route('site.categories.show', ['siteTenant' => $tenant->slug, 'topic' => 'ai']))
            ->assertOk()
            ->assertSee('ChatGPT')
            ->assertDontSee('Quiet Book');

        $this->get(route('site.categories.show', ['siteTenant' => $tenant->slug, 'topic' => 'technology']))
            ->assertOk()
            ->assertSee('Quiet Book')
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Price')
            ->assertDontSee('24.00');

        $this->get(route('site.content.show', ['siteTenant' => $tenant->slug, 'entry' => 'chatgpt']))
            ->assertOk()
            ->assertSee('Use Tool')
            ->assertSee('https://example.com/chatgpt');
    }

    public function test_theme_colors_stay_on_the_safe_palette(): void
    {
        $workspace = $this->provision('Palette', 'palette', 'owner@palette.test');

        $this->actingAs($workspace['owner'])->put(route('tenant.theme.update'), [
            'primary' => 'javascript:alert(1)',
            'secondary' => '#115e59',
            'background' => '#fafaf9',
            'text' => '#0f172a',
            'button' => '#0f766e',
            'font' => 'instrument-sans',
            'radius' => '16',
            'container' => '1120',
            'columns' => 4,
            'card_style' => 'raised',
            'header_style' => 'solid',
            'footer_style' => 'solid',
            'mode' => 'light',
            'listing' => 'pagination',
            'per_page' => 16,
        ])->assertRedirect();

        $this->get(route('site.home', ['siteTenant' => 'palette']))
            ->assertOk()
            ->assertSee('--site-primary:#0f766e', false)
            ->assertDontSee('javascript:alert', false);
    }

    public function test_a_menu_item_cannot_be_edited_from_another_workspace(): void
    {
        $first = $this->provision('One', 'one', 'owner@one.test');
        $second = $this->provision('Two', 'two', 'owner@two.test');
        $item = MenuItem::query()
            ->whereHas('menu', fn ($query) => $query->withoutGlobalScope('tenant')->where('tenant_id', $first['tenant']->id))
            ->firstOrFail();

        $this->actingAs($second['owner'])
            ->put(route('tenant.menus.update', $item), [
                'label' => 'Hijack',
                'type' => 'url',
                'url' => 'https://example.com',
                'enabled' => 1,
            ])
            ->assertNotFound();

        $this->assertNotSame('Hijack', $item->fresh()->label);
    }

    /**
     * @param  list<string>  $modules
     * @return array{tenant: Tenant, owner: User}
     */
    private function provision(string $name, string $slug, string $email, array $modules = ['posts', 'categories', 'menu-manager', 'theme-settings', 'pages']): array
    {
        return app(TenantProvisioner::class)->provision([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'admin_name' => $name.' Owner',
            'admin_email' => $email,
            'password' => 'password',
        ], $modules);
    }
}
