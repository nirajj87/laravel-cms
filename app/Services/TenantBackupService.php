<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Jobs\CreateTenantBackup;
use App\Models\Backup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantBackupService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function queue(User $user, Tenant $tenant, bool $includeFiles = false): Backup
    {
        $backup = Backup::query()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'scope' => 'tenant',
            'includes_files' => $includeFiles,
            'filename' => 'tenant-'.$tenant->slug.'-'.now()->format('Ymd-His').'.json',
            'disk' => 'local',
            'status' => BackupStatus::Pending,
        ]);

        CreateTenantBackup::dispatch($backup);
        $this->activity->log('backup.queued', 'Queued workspace backup '.$backup->filename, $backup, [
            'module' => 'backup',
            'tenant_id' => $tenant->id,
        ], $tenant->id);

        return $backup;
    }

    public function run(Backup $backup): void
    {
        $tenant = Tenant::query()->find($backup->tenant_id);

        if (! $tenant) {
            $backup->update(['status' => BackupStatus::Failed, 'error' => 'Workspace no longer exists.']);

            return;
        }

        $backup->update(['status' => BackupStatus::Running, 'error' => null]);

        try {
            $json = json_encode($this->payload($tenant), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $filename = 'tenant-'.$tenant->slug.'-'.$backup->id.'.json';
            $body = $json;
            $included = false;

            if ($backup->includes_files && class_exists(\ZipArchive::class)) {
                $zip = $this->archive($tenant, $json);

                if ($zip !== null) {
                    $body = $zip;
                    $filename = 'tenant-'.$tenant->slug.'-'.$backup->id.'.zip';
                    $included = true;
                }
            }

            $path = 'backups/'.$filename.'.enc';
            Storage::disk('local')->put($path, Crypt::encryptString($body));

            $backup->update([
                'filename' => $filename,
                'path' => $path,
                'size' => Storage::disk('local')->size($path),
                'includes_files' => $included,
                'status' => BackupStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->prune($tenant);
        } catch (\Throwable $exception) {
            $backup->update([
                'status' => BackupStatus::Failed,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Tenant $tenant): array
    {
        $id = $tenant->id;
        $users = DB::table('users')->where('tenant_id', $id)->get()->map(function ($user) {
            unset($user->two_factor_secret, $user->two_factor_recovery_codes, $user->remember_token);

            return $user;
        });
        $roles = DB::table('roles')->where('tenant_id', $id)->get();
        $roleIds = $roles->pluck('id');
        $posts = DB::table('posts')->where('tenant_id', $id)->get();
        $postIds = $posts->pluck('id');
        $types = DB::table('content_types')->where('tenant_id', $id)->get();
        $menus = DB::table('menus')->where('tenant_id', $id)->get();
        $tenantRow = DB::table('tenants')->where('id', $id)->first();

        if ($tenantRow && isset($tenantRow->settings)) {
            $settings = json_decode((string) $tenantRow->settings, true);

            if (is_array($settings) && isset($settings['email']['password'])) {
                $settings['email']['password'] = null;
                $tenantRow->settings = json_encode($settings);
            }
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'tenant' => $tenantRow,
            'users' => $users,
            'roles' => $roles,
            'role_user' => DB::table('role_user')->whereIn('role_id', $roleIds)->get(),
            'role_permission' => DB::table('permission_role')->whereIn('role_id', $roleIds)->get(),
            'categories' => DB::table('categories')->where('tenant_id', $id)->get(),
            'content_types' => $types,
            'content_type_fields' => DB::table('content_type_fields')->whereIn('content_type_id', $types->pluck('id'))->get(),
            'posts' => $posts,
            'post_field_values' => DB::table('post_field_values')->whereIn('post_id', $postIds)->get(),
            'category_post' => DB::table('category_post')->whereIn('post_id', $postIds)->get(),
            'media' => DB::table('media')->where('tenant_id', $id)->get(),
            'pages' => DB::table('pages')->where('tenant_id', $id)->get(),
            'menus' => $menus,
            'menu_items' => DB::table('menu_items')->whereIn('menu_id', $menus->pluck('id'))->get(),
            'layout_blocks' => DB::table('layout_blocks')->where('tenant_id', $id)->get(),
            'feedback' => DB::table('feedback')->where('tenant_id', $id)->get(),
            'email_templates' => DB::table('email_templates')->where('tenant_id', $id)->get(),
        ];
    }

    public function download(Backup $backup): string
    {
        $contents = Storage::disk($backup->disk)->get($backup->path);

        return Crypt::decryptString((string) $contents);
    }

    private function archive(Tenant $tenant, string $json): ?string
    {
        $temporary = tempnam(sys_get_temp_dir(), 'backup');

        if ($temporary === false) {
            return null;
        }

        $zip = new \ZipArchive;

        if ($zip->open($temporary, \ZipArchive::OVERWRITE) !== true) {
            @unlink($temporary);

            return null;
        }

        $zip->addFromString('data.json', $json);
        $prefix = 'tenants/'.$tenant->id.'/';

        foreach (Storage::disk('public')->allFiles('tenants/'.$tenant->id) as $path) {
            if (! str_starts_with($path, $prefix) || str_contains($path, '..')) {
                continue;
            }

            $zip->addFromString('files/'.substr($path, strlen($prefix)), (string) Storage::disk('public')->get($path));
        }

        $zip->close();
        $binary = file_get_contents($temporary);
        @unlink($temporary);

        return $binary === false ? null : $binary;
    }

    private function prune(Tenant $tenant): void
    {
        $keep = max(1, min(100, (int) ($tenant->setting('backup.retention', 5) ?? 5)));

        Backup::query()
            ->where('tenant_id', $tenant->id)
            ->where('scope', 'tenant')
            ->where('status', BackupStatus::Completed)
            ->orderByDesc('id')
            ->skip($keep)
            ->take(50)
            ->get()
            ->each(function (Backup $backup) {
                if ($backup->path) {
                    Storage::disk($backup->disk)->delete($backup->path);
                }

                $backup->delete();
            });
    }
}
