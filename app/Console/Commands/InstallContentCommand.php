<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ContentCatalog;
use App\Services\ModuleRegistry;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class InstallContentCommand extends Command
{
    protected $signature = 'content:install';

    protected $description = 'Sync content permissions and seed starter types, categories, and sample posts';

    public function handle(ModuleRegistry $registry, ContentCatalog $catalog, TenantContext $context): int
    {
        $registry->sync();

        foreach (Tenant::query()->orderBy('id')->get() as $tenant) {
            $context->set($tenant);
            $catalog->grantTemplatePermissions($tenant);
            $catalog->ensureStarterContent($tenant);
            $catalog->seedSamplePosts($tenant);
            $this->line('Installed content for '.$tenant->name);
        }

        $context->clear();
        $this->info('Content catalog is ready.');

        return self::SUCCESS;
    }
}
