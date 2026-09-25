<?php

namespace App\Console\Commands;

use App\Services\ShowcaseCatalog;
use Illuminate\Console\Command;

class ShowcaseContentCommand extends Command
{
    protected $signature = 'content:showcase';

    protected $description = 'Create the Meridian and Sable workspaces and fill every category';

    public function handle(ShowcaseCatalog $showcase): int
    {
        $showcase->ensure();
        $this->info('Meridian Works and Sable Press are ready.');

        return self::SUCCESS;
    }
}
