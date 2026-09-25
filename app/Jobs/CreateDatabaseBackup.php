<?php

namespace App\Jobs;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDatabaseBackup implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 1;

    public function __construct(public Backup $backup) {}

    public function handle(BackupService $backups): void
    {
        $backups->run($this->backup);
    }

    public function failed(\Throwable $exception): void
    {
        $this->backup->update([
            'status' => BackupStatus::Failed,
            'error' => $exception->getMessage(),
        ]);
    }
}
