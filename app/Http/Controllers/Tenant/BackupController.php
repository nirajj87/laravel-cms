<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\TenantBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(private readonly TenantBackupService $backups) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->hasPermission('backup.view'), 403);
        $tenant = current_tenant();

        return view('tenant.backups.index', [
            'backups' => Backup::query()->where('tenant_id', $tenant->id)->where('scope', 'tenant')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('backup.create'), 403);
        $this->backups->queue($request->user(), current_tenant(), $request->boolean('include_files'));

        return back()->with('status', 'Backup queued. The queue worker writes the file outside the public web root.');
    }

    public function download(int $backup): StreamedResponse
    {
        $record = Backup::query()
            ->whereKey($backup)
            ->where('tenant_id', current_tenant()->id)
            ->where('scope', 'tenant')
            ->firstOrFail();
        $this->authorize('download', $record);
        abort_unless($record->path, 404);

        $filename = $this->filename($record->filename);
        $body = $this->backups->download($record);

        return response()->streamDownload(function () use ($body) {
            echo $body;
        }, $filename, [
            'Content-Type' => str_ends_with($filename, '.zip') ? 'application/zip' : 'application/json',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function destroy(int $backup): RedirectResponse
    {
        $record = Backup::query()
            ->whereKey($backup)
            ->where('tenant_id', current_tenant()->id)
            ->where('scope', 'tenant')
            ->firstOrFail();
        $this->authorize('delete', $record);

        if ($record->path) {
            Storage::disk($record->disk)->delete($record->path);
        }

        $record->delete();

        return back()->with('status', 'Backup deleted.');
    }

    public static function signedUrl(Backup $backup): string
    {
        return URL::temporarySignedRoute('tenant.backups.download', now()->addMinutes(10), ['backup' => $backup->id]);
    }

    private function filename(string $filename): string
    {
        $name = basename($filename);

        return preg_match('/^[A-Za-z0-9._-]+$/', $name) ? $name : 'backup.json';
    }
}
