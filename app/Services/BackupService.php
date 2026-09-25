<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Jobs\CreateDatabaseBackup;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function queue(User $user): Backup
    {
        $backup = Backup::query()->create([
            'user_id' => $user->id,
            'scope' => 'platform',
            'filename' => 'backup-'.now()->format('Ymd-His').'.sql',
            'disk' => 'local',
            'status' => BackupStatus::Pending,
        ]);

        CreateDatabaseBackup::dispatch($backup);

        $this->activity->log('backup.queued', 'Queued database backup '.$backup->filename, $backup);

        return $backup;
    }

    public function run(Backup $backup): void
    {
        $backup->update(['status' => BackupStatus::Running, 'error' => null]);

        if (config('database.default') !== 'mysql') {
            $backup->update([
                'status' => BackupStatus::Failed,
                'error' => 'Database backups require the MySQL connection.',
            ]);

            return;
        }

        $relative = 'backups/'.$backup->filename;
        Storage::disk('local')->makeDirectory('backups');
        $absolute = Storage::disk('local')->path($relative);
        $config = config('database.connections.mysql');
        $cnf = storage_path('app/private/backups/.cnf-'.$backup->id);

        file_put_contents($cnf, implode("\n", [
            '[client]',
            'host='.$config['host'],
            'port='.($config['port'] ?? 3306),
            'user='.$config['username'],
            'password='.$config['password'],
        ]));

        try {
            $process = new Process([
                'mysqldump',
                '--defaults-extra-file='.$cnf,
                '--single-transaction',
                '--quick',
                '--no-tablespaces',
                $config['database'],
            ]);
            $process->setTimeout(180);

            $handle = fopen($absolute, 'wb');

            $process->run(function ($type, $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });

            fclose($handle);

            if (! $process->isSuccessful()) {
                Storage::disk('local')->delete($relative);

                $backup->update([
                    'status' => BackupStatus::Failed,
                    'error' => trim($process->getErrorOutput()) ?: 'mysqldump failed.',
                ]);

                return;
            }

            $backup->update([
                'path' => $relative,
                'size' => Storage::disk('local')->size($relative),
                'status' => BackupStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->prune();
        } finally {
            @unlink($cnf);
        }
    }

    public function delete(Backup $backup): void
    {
        if ($backup->path) {
            Storage::disk('local')->delete($backup->path);
        }

        $filename = $backup->filename;
        $backup->delete();
        $this->activity->log('backup.deleted', 'Deleted backup '.$filename);
    }

    private function prune(): void
    {
        $keep = (int) app(PlatformSettingsService::class)->get('system', 'backup_retention', 10);

        if ($keep < 1) {
            return;
        }

        Backup::query()
            ->where('scope', 'platform')
            ->where('status', BackupStatus::Completed)
            ->orderByDesc('id')
            ->skip($keep)
            ->take(100)
            ->get()
            ->each(fn (Backup $backup) => $this->delete($backup));
    }
}
