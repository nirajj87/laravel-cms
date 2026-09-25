@extends('layouts.app')

@section('title', 'Backups')
@section('kicker', 'System')
@section('heading', 'Backups')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-2xl text-sm text-slate-600">Workspace backups are queued, encrypted, and stored outside the public web root. Download links expire after 10 minutes and still require permission.</p>
        <form method="POST" action="{{ route('tenant.backups.store') }}" class="flex items-center gap-3">
            @csrf
            <label class="text-sm"><input type="checkbox" name="include_files" value="1"> Include uploaded files</label>
            <button class="btn btn-primary" type="submit">Create backup</button>
        </form>
    </div>
    <div class="card overflow-hidden">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-stone-50 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">File</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Size</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($backups as $backup)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $backup->filename }}</p>
                            <p class="text-xs text-slate-500">{{ $backup->created_at?->diffForHumans() }}</p>
                            @if ($backup->error)<p class="mt-1 text-xs text-red-700">{{ $backup->error }}</p>@endif
                        </td>
                        <td class="px-4 py-3">{{ $backup->status->label() }}</td>
                        <td class="px-4 py-3">{{ $backup->size ? number_format($backup->size / 1024, 1).' KB' : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($backup->path)
                                <a class="font-medium text-teal-800" href="{{ \App\Http\Controllers\Tenant\BackupController::signedUrl($backup) }}">Download</a>
                            @endif
                            <form class="inline" method="POST" action="{{ route('tenant.backups.destroy', $backup) }}" onsubmit="return confirm('Delete this backup?')">
                                @csrf
                                @method('DELETE')
                                <button class="ml-3 text-red-700" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No backups yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $backups->links() }}</div>
@endsection
