<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreMediaRequest;
use App\Http\Requests\Tenant\UpdateMediaRequest;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(private readonly MediaLibrary $library) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MediaAsset::class);

        $assets = MediaAsset::query()
            ->with('folder')
            ->when($request->string('q')->toString(), function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('original_name', 'like', '%'.$term.'%')
                        ->orWhere('alt', 'like', '%'.$term.'%')
                        ->orWhere('caption', 'like', '%'.$term.'%');
                });
            })
            ->when($request->string('kind')->toString(), fn ($query, $kind) => $query->where('kind', $kind))
            ->when($request->integer('folder'), fn ($query, $folder) => $query->where('folder_id', $folder))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('tenant.media.index', [
            'assets' => $assets,
            'folders' => MediaFolder::query()->orderBy('name')->get(),
            'editing' => $request->filled('edit')
                ? MediaAsset::query()->whereKey($request->integer('edit'))->first()
                : null,
        ]);
    }

    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $this->library->store(
            $request->file('file'),
            $request->integer('folder_id') ?: null,
            $request->user(),
            null,
            $request->validated('alt'),
            $request->validated('caption'),
        );

        return redirect()->route('tenant.media.index')->with('status', 'File uploaded.');
    }

    public function update(UpdateMediaRequest $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $this->library->update($mediaAsset, $request->validated());

        return redirect()->route('tenant.media.index')->with('status', 'File details saved.');
    }

    public function destroy(MediaAsset $mediaAsset): RedirectResponse
    {
        $this->authorize('delete', $mediaAsset);
        $this->library->delete($mediaAsset);

        return redirect()->route('tenant.media.index')->with('status', 'File deleted.');
    }

    public function storeFolder(Request $request): RedirectResponse
    {
        $this->authorize('create', MediaAsset::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $this->library->createFolder($data['name'], $data['parent_id'] ?? null);

        return redirect()->route('tenant.media.index')->with('status', 'Folder created.');
    }

    public function destroyFolder(MediaFolder $mediaFolder): RedirectResponse
    {
        abort_unless(request()->user()?->hasPermission('media.delete'), 403);
        $this->library->deleteFolder($mediaFolder);

        return redirect()->route('tenant.media.index')->with('status', 'Folder deleted.');
    }
}
