<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\PlatformSettingsService;
use App\Services\ShowcaseCatalog;
use App\Services\TenantProvisioner;
use App\Services\TenantUserService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(PlatformSettingsService::class);
        $definitions = $settings->definitions();

        $settings->updateGroup('general', [
            'platform_name' => 'Content Platform',
            'tagline' => 'Configurable workspaces for every kind of content.',
        ], $definitions['general']);

        $settings->updateGroup('email', [
            'mailer' => 'log',
            'host' => null,
            'port' => 587,
            'username' => null,
            'from_address' => 'hello@example.com',
            'from_name' => 'Content Platform',
        ], $definitions['email']);

        $settings->updateGroup('seo', [
            'default_title' => 'Content Platform',
            'default_description' => 'Multi-tenant content for tools, services, articles, and custom types.',
            'default_keywords' => 'content, tenants, publishing',
            'robots' => 'index,follow',
        ], $definitions['seo']);

        $settings->updateGroup('system', [
            'support_email' => 'support@platform.test',
            'default_timezone' => 'UTC',
            'backup_retention' => 10,
        ], $definitions['system']);

        $admin = new User;
        $admin->forceFill([
            'name' => 'Super Admin',
            'email' => 'admin@platform.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);
        $admin->save();

        $result = app(TenantProvisioner::class)->provision([
            'name' => 'Northwind Studio',
            'slug' => 'northwind',
            'subdomain' => 'northwind',
            'email' => 'hello@northwind.test',
            'phone' => null,
            'address' => null,
            'status' => 'active',
            'admin_name' => 'Ava Shah',
            'admin_email' => 'owner@northwind.test',
            'password' => 'password',
        ], array_keys(config('modules.catalog')));

        $editorRoleId = Role::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $result['tenant']->id)
            ->where('slug', 'tenant-editor')
            ->value('id');

        app(TenantUserService::class)->create($result['tenant'], [
            'name' => 'Northwind Editor',
            'email' => 'editor@northwind.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'role_id' => $editorRoleId,
        ], $result['owner']);

        app(ShowcaseCatalog::class)->ensure();
    }
}
