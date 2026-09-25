<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModuleRegistry;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        app(ModuleRegistry::class)->sync();
    }

    public function test_super_admin_can_provision_a_tenant_and_the_owner_can_sign_in(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'email' => 'admin@platform.test',
        ]);

        $response = $this->actingAs($admin)->post(route('platform.tenants.store'), [
            'name' => 'Acme Studio',
            'slug' => 'acme',
            'subdomain' => 'acme',
            'status' => 'active',
            'admin_name' => 'Acme Owner',
            'admin_email' => 'owner@acme.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'modules' => ['posts', 'media'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', ['slug' => 'acme']);

        auth()->logout();

        $this->post('/login', [
            'email' => 'owner@acme.test',
            'password' => 'password',
        ])->assertRedirect(route('tenant.dashboard'));

        $this->get(route('tenant.dashboard'))->assertOk();
        $this->get(route('tenant.posts.index'))->assertOk();
    }

    public function test_a_tenant_cannot_see_another_tenants_users(): void
    {
        $first = $this->provision('Alpha', 'alpha', 'owner@alpha.test');
        $second = $this->provision('Beta', 'beta', 'owner@beta.test');

        $this->actingAs($first['owner'])
            ->get(route('tenant.users.index'))
            ->assertOk()
            ->assertSee('owner@alpha.test')
            ->assertDontSee('owner@beta.test');

        $this->actingAs($first['owner'])
            ->get(route('tenant.users.edit', $second['owner']))
            ->assertNotFound();
    }

    public function test_a_disabled_module_rejects_its_route(): void
    {
        $workspace = $this->provision('Gamma', 'gamma', 'owner@gamma.test', []);

        $this->actingAs($workspace['owner'])
            ->get(route('tenant.posts.index'))
            ->assertForbidden();
    }

    public function test_missing_permission_is_rejected_on_the_backend(): void
    {
        $workspace = $this->provision('Delta', 'delta', 'owner@delta.test');
        $editor = User::factory()->create([
            'tenant_id' => $workspace['tenant']->id,
            'email' => 'editor@delta.test',
        ]);
        $roleId = Role::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $workspace['tenant']->id)
            ->where('slug', 'tenant-editor')
            ->value('id');
        $editor->roles()->attach($roleId, ['tenant_id' => $workspace['tenant']->id]);

        $this->actingAs($editor)
            ->delete(route('tenant.users.destroy', $workspace['owner']))
            ->assertForbidden();
    }

    public function test_tenant_users_cannot_open_the_platform(): void
    {
        $workspace = $this->provision('Echo', 'echo', 'owner@echo.test');

        $this->actingAs($workspace['owner'])
            ->get(route('platform.dashboard'))
            ->assertForbidden();
    }

    public function test_an_inactive_tenant_cannot_sign_in(): void
    {
        $workspace = $this->provision('Foxtrot', 'foxtrot', 'owner@foxtrot.test');
        $workspace['tenant']->update(['status' => TenantStatus::Inactive]);

        $this->post('/login', [
            'email' => 'owner@foxtrot.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * @param  list<string>  $modules
     * @return array{tenant: Tenant, owner: User}
     */
    private function provision(string $name, string $slug, string $email, array $modules = ['posts', 'users']): array
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
