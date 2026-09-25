<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\Tenant;
use App\Services\BackupService;
use App\Services\TenantBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): View
    {
        $this->authorize('viewAny', Backup::class);

        return view('platform.backups.index', [
            'backups' => Backup::query()->with(['user:id,name', 'tenant:id,name'])->latest()->paginate(15),
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Backup::class);

        if ($request->boolean('all_tenants')) {
            Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($request) {
                app(TenantBackupService::class)->queue($request->user(), $tenant, $request->boolean('include_files'));
            });

            return back()->with('status', 'A backup was queued for every workspace.');
        }

        if ($request->filled('tenant_id')) {
            $tenant = Tenant::query()->findOrFail($request->integer('tenant_id'));
            app(TenantBackupService::class)->queue($request->user(), $tenant, $request->boolean('include_files'));

            return back()->with('status', 'Workspace backup queued.');
        }

        $this->backups->queue($request->user());

        return back()->with('status', 'Backup queued. The queue worker will write the SQL file.');
    }

    public function download(Backup $backup): StreamedResponse
    {
        $this->authorize('download', $backup);
        abort_unless($backup->path && Storage::disk($backup->disk)->exists($backup->path), 404);

        if ($backup->scope === 'tenant') {
            $body = app(TenantBackupService::class)->download($backup);
            $filename = basename($backup->filename);

            return response()->streamDownload(function () use ($body) {
                echo $body;
            }, $filename, ['Cache-Control' => 'no-store']);
        }

        return Storage::disk($backup->disk)->download($backup->path, $backup->filename);
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);
        $this->backups->delete($backup);

        return back()->with('status', 'Backup deleted.');
    }
}
