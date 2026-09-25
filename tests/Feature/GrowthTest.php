<?php

namespace Tests\Feature;

use App\Mail\WorkspaceMessage;
use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\ContentType;
use App\Models\Feedback;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ResetPasswordLink;
use App\Services\ModuleRegistry;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GrowthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
        app(ModuleRegistry::class)->sync();
    }

    public function test_sitemap_robots_and_analytics_stay_on_safe_values(): void
    {
        $workspace = $this->provision();
        $tenant = $workspace['tenant'];
        $book = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', 'book')->firstOrFail();

        $this->actingAs($workspace['owner'])->post(route('tenant.posts.store'), [
            'content_type_id' => $book->id,
            'title' => 'Public Book',
            'status' => 'published',
            'fields' => ['author' => 'Ada Lovelace'],
        ])->assertRedirect();

        $this->actingAs($workspace['owner'])->post(route('tenant.posts.store'), [
            'content_type_id' => $book->id,
            'title' => 'Hidden Draft',
            'status' => 'draft',
        ])->assertRedirect();

        $this->get(route('site.sitemap', ['siteTenant' => $tenant->slug]))
            ->assertOk()
            ->assertSee('/content/public-book', false)
            ->assertDontSee('hidden-draft', false);

        $this->actingAs($workspace['owner'])->put(route('tenant.seo.update'), [
            'robots' => 'noindex,nofollow',
            'site_title' => 'Northwind',
        ])->assertRedirect();

        $this->get(route('site.robots', ['siteTenant' => $tenant->slug]))
            ->assertOk()
            ->assertSee('Disallow: /');

        $this->actingAs($workspace['owner'])->put(route('tenant.analytics.update'), [
            'enabled' => 1,
            'measurement_id' => 'javascript:alert(1)',
        ])->assertRedirect();

        $this->get(route('site.home', ['siteTenant' => $tenant->slug]))
            ->assertOk()
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('googletagmanager.com', false);

        $this->actingAs($workspace['owner'])->put(route('tenant.analytics.update'), [
            'enabled' => 1,
            'measurement_id' => 'G-AB12CD34',
        ])->assertRedirect();

        $this->get(route('site.home', ['siteTenant' => $tenant->slug]))
            ->assertOk()
            ->assertSee('G-AB12CD34', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('WebSite', false);
    }

    public function test_email_passwords_are_not_written_to_the_audit_log(): void
    {
        $workspace = $this->provision();

        $this->actingAs($workspace['owner'])->put(route('tenant.email.update'), [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'mailer',
            'password' => 'SuperSecret123',
            'encryption' => 'tls',
            'from_name' => 'Northwind',
            'from_email' => 'hello@northwind.test',
        ])->assertRedirect();

        $log = ActivityLog::query()->where('action', 'email.updated')->first();
        $encoded = json_encode($log?->properties).$log?->description;

        $this->assertStringNotContainsString('SuperSecret123', $encoded);
        $this->assertStringStartsWith('enc:', (string) $workspace['tenant']->fresh()->setting('email.password'));
    }

    public function test_feedback_is_isolated_and_contact_mail_is_queued(): void
    {
        $first = $this->provision();
        $second = $this->provision('Second', 'second', 'owner@second.test');

        $this->post(route('site.feedback.store', ['siteTenant' => $first['tenant']->slug]), [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'rating' => 5,
            'comment' => 'Clear and useful.',
        ])->assertRedirect();

        $feedback = Feedback::withoutGlobalScope('tenant')->where('tenant_id', $first['tenant']->id)->firstOrFail();
        $this->assertSame('pending', $feedback->status->value);

        $this->actingAs($second['owner'])
            ->put(route('tenant.feedback.update', $feedback), ['status' => 'published'])
            ->assertNotFound();

        $this->post(route('site.contact.store', ['siteTenant' => $first['tenant']->slug]), [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'message' => 'Hello from the site.',
        ])->assertRedirect();

        Mail::assertSent(WorkspaceMessage::class);
        $this->assertContains('web', app('router')->getRoutes()->getByName('site.feedback.store')->gatherMiddleware());
    }

    public function test_failed_sign_in_does_not_record_the_password(): void
    {
        $this->post(route('login'), [
            'email' => 'missing@example.com',
            'password' => 'wrong-secret',
        ])->assertSessionHasErrors('email');

        $log = ActivityLog::query()->where('action', 'auth.failed')->first();

        $this->assertNotNull($log);
        $this->assertSame('missing@example.com', $log->properties['email'] ?? null);
        $this->assertStringNotContainsString('wrong-secret', json_encode($log->properties));
    }

    public function test_a_password_reset_link_can_be_used_once(): void
    {
        $workspace = $this->provision();
        $token = null;

        $this->post(route('password.email'), ['email' => $workspace['owner']->email])->assertRedirect();

        Notification::assertSentTo($workspace['owner'], ResetPasswordLink::class, function (ResetPasswordLink $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $workspace['owner']->email,
            'password' => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-9', $workspace['owner']->fresh()->password));
    }

    public function test_a_workspace_backup_is_private_and_isolated(): void
    {
        $first = $this->provision();
        $second = $this->provision('Second', 'second', 'owner@second.test');
        $book = ContentType::withoutGlobalScope('tenant')->where('tenant_id', $first['tenant']->id)->where('slug', 'book')->firstOrFail();

        $this->actingAs($first['owner'])->post(route('tenant.posts.store'), [
            'content_type_id' => $book->id,
            'title' => 'BackupMarkerTitle',
            'status' => 'draft',
        ])->assertRedirect();

        $this->actingAs($first['owner'])->post(route('tenant.backups.store'))->assertRedirect();

        $backup = Backup::query()->where('tenant_id', $first['tenant']->id)->firstOrFail();

        $this->assertSame('completed', $backup->status->value);
        $this->assertStringStartsWith('backups/', (string) $backup->path);
        $this->assertStringNotContainsString('BackupMarkerTitle', Storage::disk('local')->get($backup->path));
        $this->assertFileDoesNotExist(public_path('storage/'.$backup->path));

        $url = URL::temporarySignedRoute('tenant.backups.download', now()->addMinutes(5), ['backup' => $backup->id]);

        $download = $this->actingAs($first['owner'])->get($url)->assertOk();
        $this->assertStringContainsString('BackupMarkerTitle', $download->streamedContent());
        $this->actingAs($second['owner'])->get($url)->assertNotFound();
    }

    /**
     * @param  list<string>  $modules
     * @return array{tenant: Tenant, owner: User}
     */
    private function provision(string $name = 'Northwind', string $slug = 'northwind', string $email = 'owner@northwind.test', array $modules = ['posts', 'categories', 'seo', 'analytics', 'email', 'feedback', 'backup']): array
    {
        return app(TenantProvisioner::class)->provision([
            'name' => $name,
            'slug' => $slug,
            'email' => 'hello@'.$slug.'.test',
            'status' => 'active',
            'admin_name' => $name.' Owner',
            'admin_email' => $email,
            'password' => 'password',
        ], $modules);
    }
}
