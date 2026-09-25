<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentType;
use App\Models\ContentTypeField;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModuleRegistry;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        app(ModuleRegistry::class)->sync();
    }

    public function test_categories_nest_and_stay_inside_the_tenant(): void
    {
        $first = $this->provision('North', 'north', 'owner@north.test');
        $second = $this->provision('South', 'south', 'owner@south.test');

        $parent = Category::withoutGlobalScope('tenant')->where('tenant_id', $first['tenant']->id)->where('slug', 'ai')->firstOrFail();
        $child = Category::withoutGlobalScope('tenant')->where('tenant_id', $first['tenant']->id)->where('slug', 'ai-tools')->firstOrFail();
        $this->assertSame($parent->id, $child->parent_id);

        $this->actingAs($first['owner'])->post(route('tenant.categories.store'), [
            'name' => 'AI Audio',
            'slug' => 'ai-audio',
            'parent_id' => $parent->id,
            'status' => 'active',
            'sort_order' => 4,
        ])->assertRedirect();

        $added = Category::withoutGlobalScope('tenant')->where('tenant_id', $first['tenant']->id)->where('slug', 'ai-audio')->firstOrFail();
        $this->assertSame($parent->id, $added->parent_id);

        $this->actingAs($first['owner'])
            ->get(route('tenant.categories.index'))
            ->assertOk()
            ->assertSee('AI Audio');

        $this->actingAs($second['owner'])
            ->get(route('tenant.categories.edit', $parent))
            ->assertNotFound();
    }

    public function test_disabled_fields_are_hidden_and_not_required(): void
    {
        $workspace = $this->provision('Bookshelf', 'bookshelf', 'owner@bookshelf.test');
        $book = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $workspace['tenant']->id)->where('slug', 'book')->firstOrFail();
        ContentTypeField::query()->where('content_type_id', $book->id)->where('key', 'price')->update([
            'enabled' => false,
            'required' => true,
        ]);

        $this->actingAs($workspace['owner'])->post(route('tenant.posts.store'), [
            'content_type_id' => $book->id,
            'title' => 'Quiet Circuits',
            'status' => 'published',
            'fields' => [
                'author' => 'Ada Lovelace',
                'short_description' => 'A quiet book',
                'buy_now' => ['url' => 'https://example.com/buy', 'label' => 'Buy now'],
            ],
        ])->assertRedirect();

        $post = Post::withoutGlobalScope('tenant')->where('title', 'Quiet Circuits')->firstOrFail();

        $this->get(route('site.entries.show', [
            'siteTenant' => $workspace['tenant']->slug,
            'typeSlug' => 'book',
            'entry' => $post->slug,
        ]))
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Price');

        ContentTypeField::query()->where('content_type_id', $book->id)->where('key', 'detail_page')->update(['enabled' => false]);

        $this->get(route('site.entries.show', [
            'siteTenant' => $workspace['tenant']->slug,
            'typeSlug' => 'book',
            'entry' => $post->slug,
        ]))->assertNotFound();

        $this->get(route('site.types.show', [
            'siteTenant' => $workspace['tenant']->slug,
            'typeSlug' => 'book',
        ]))
            ->assertOk()
            ->assertSee('Quiet Circuits')
            ->assertSee('https://example.com/buy');
    }

    public function test_an_editor_cannot_publish_or_change_content_types(): void
    {
        $workspace = $this->provision('Desk', 'desk', 'owner@desk.test');
        $editor = User::factory()->create([
            'tenant_id' => $workspace['tenant']->id,
            'email' => 'editor@desk.test',
        ]);
        $role = Role::withoutGlobalScope('tenant')
            ->where('tenant_id', $workspace['tenant']->id)
            ->where('slug', 'tenant-editor')
            ->firstOrFail();
        $editor->roles()->attach($role->id, ['tenant_id' => $workspace['tenant']->id]);

        $article = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $workspace['tenant']->id)->where('slug', 'article')->firstOrFail();

        $this->actingAs($editor)
            ->get(route('tenant.posts.types.index'))
            ->assertForbidden();

        $this->actingAs($editor)->post(route('tenant.posts.store'), [
            'content_type_id' => $article->id,
            'title' => 'Unpublished note',
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->actingAs($editor)->post(route('tenant.posts.store'), [
            'content_type_id' => $article->id,
            'title' => 'Unpublished note',
            'status' => 'draft',
        ])->assertRedirect();

        $post = Post::withoutGlobalScope('tenant')->where('title', 'Unpublished note')->firstOrFail();
        $this->assertSame('draft', $post->status->value);

        $this->get(route('site.entries.show', [
            'siteTenant' => $workspace['tenant']->slug,
            'typeSlug' => 'article',
            'entry' => $post->slug,
        ]))->assertNotFound();
    }

    public function test_media_uploads_store_a_file_path(): void
    {
        Storage::fake('public');
        $workspace = $this->provision('Archive', 'archive', 'owner@archive.test');

        $this->actingAs($workspace['owner'])->post(route('tenant.media.store'), [
            'file' => UploadedFile::fake()->image('cover.jpg', 40, 40),
            'alt' => 'Cover photo',
            'caption' => 'A small cover',
        ])->assertRedirect();

        $asset = MediaAsset::withoutGlobalScope('tenant')->firstOrFail();

        $this->assertSame('cover.jpg', $asset->original_name);
        $this->assertSame('image', $asset->kind);
        $this->assertStringStartsWith('tenants/'.$workspace['tenant']->id.'/media/', $asset->path);
        $this->assertLessThan(255, strlen($asset->path));
        Storage::disk('public')->assertExists($asset->path);
        $this->assertIsInt($asset->size);
    }

    /**
     * @param  list<string>  $modules
     * @return array{tenant: Tenant, owner: User}
     */
    private function provision(string $name, string $slug, string $email, array $modules = ['posts', 'categories', 'media']): array
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
