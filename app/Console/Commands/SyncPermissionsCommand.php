<?php

namespace App\Console\Commands;

use App\Services\ModuleRegistry;
use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync {--discover : Report route permissions that are missing from the catalog}';

    protected $description = 'Sync modules and permissions from the application catalog';

    public function handle(ModuleRegistry $registry): int
    {
        $result = $registry->sync();

        $this->info("Modules synced: {$result['modules']}. Permissions synced: {$result['permissions']}.");

        if (! $this->option('discover')) {
            return self::SUCCESS;
        }

        $missing = $registry->missingRoutePermissions();

        if ($missing === []) {
            $this->info('Every route permission middleware entry is registered.');

            return self::SUCCESS;
        }

        foreach ($missing as $slug) {
            $this->warn('Missing permission: '.$slug);
        }

        return self::FAILURE;
    }
}
