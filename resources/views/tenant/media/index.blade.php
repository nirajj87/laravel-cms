@extends('layouts.app')

@section('title', 'Media')
@section('kicker', 'Content')
@section('heading', 'Media library')

@section('content')
    <div class="mb-4 grid gap-4 lg:grid-cols-[1fr_280px]">
        <form method="POST" action="{{ route('tenant.media.store') }}" enctype="multipart/form-data" class="card grid gap-3 p-4 sm:grid-cols-2">
            @csrf
            <x-field label="File" name="file">
                <input class="field" type="file" name="file" required>
            </x-field>
            <x-field label="Folder" name="folder_id">
                <select class="field" name="folder_id">
                    <option value="">None</option>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder->id }}" @selected((string) request('folder') === (string) $folder->id)>{{ $folder->name }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="Alt text" name="alt">
                <input class="field" name="alt">
            </x-field>
            <x-field label="Caption" name="caption">
                <input class="field" name="caption">
            </x-field>
            @permission('media.create')
                <button class="btn btn-primary sm:col-span-2" type="submit">Upload</button>
            @endpermission
        </form>
        <form method="POST" action="{{ route('tenant.media.folders.store') }}" class="card p-4">
            @csrf
            <x-field label="New folder" name="name">
                <input class="field" name="name" required>
            </x-field>
            @permission('media.create')
                <button class="btn btn-secondary" type="submit">Create folder</button>
            @endpermission
        </form>
    </div>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4">
        <input class="field" name="q" value="{{ request('q') }}" placeholder="Search name, alt, caption">
        <select class="field" name="kind">
            <option value="">All kinds</option>
            @foreach (['image' => 'Images', 'document' => 'Documents', 'video' => 'Videos', 'other' => 'Files'] as $kind => $label)
                <option value="{{ $kind }}" @selected(request('kind') === $kind)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="field" name="folder">
            <option value="">All folders</option>
            @foreach ($folders as $folder)
                <option value="{{ $folder->id }}" @selected((string) request('folder') === (string) $folder->id)>{{ $folder->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>

    @if ($folders->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($folders as $folder)
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm ring-1 ring-stone-200">
                    <a href="{{ route('tenant.media.index', ['folder' => $folder->id]) }}">{{ $folder->name }}</a>
                    @permission('media.delete')
                        <form method="POST" action="{{ route('tenant.media.folders.destroy', $folder) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-700" type="submit" aria-label="Delete {{ $folder->name }}">×</button>
                        </form>
                    @endpermission
                </span>
            @endforeach
        </div>
    @endif

    @if ($editing)
        <form method="POST" action="{{ route('tenant.media.update', $editing) }}" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4">
            @csrf
            @method('PUT')
            <x-field label="Alt text" name="alt">
                <input class="field" name="alt" value="{{ old('alt', $editing->alt) }}">
            </x-field>
            <x-field label="Caption" name="caption">
                <input class="field" name="caption" value="{{ old('caption', $editing->caption) }}">
            </x-field>
            <x-field label="Folder" name="folder_id">
                <select class="field" name="folder_id">
                    <option value="">None</option>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder->id }}" @selected((string) old('folder_id', $editing->folder_id) === (string) $folder->id)>{{ $folder->name }}</option>
                    @endforeach
                </select>
            </x-field>
            <div class="flex items-end">
                <button class="btn btn-primary" type="submit">Save details</button>
            </div>
        </form>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($assets as $asset)
            <article class="card overflow-hidden">
                @if ($asset->isImage())
                    <img src="{{ $asset->url() }}" alt="{{ $asset->alt ?: $asset->original_name }}" class="h-40 w-full object-cover">
                @else
                    <div class="flex h-40 items-center justify-center bg-stone-50 text-sm text-slate-500">{{ strtoupper($asset->extension ?: $asset->kind) }}</div>
                @endif
                <div class="space-y-2 p-4">
                    <p class="truncate text-sm font-medium">{{ $asset->original_name }}</p>
                    <p class="text-xs text-slate-500">{{ $asset->kind }} · {{ number_format($asset->size / 1024, 1) }} KB @if ($asset->folder) · {{ $asset->folder->name }} @endif</p>
                    @if ($asset->alt)
                        <p class="text-xs text-slate-500">Alt: {{ $asset->alt }}</p>
                    @endif
                    <div class="flex gap-2">
                        @permission('media.edit')
                            <a class="btn btn-secondary" href="{{ route('tenant.media.index', array_merge(request()->query(), ['edit' => $asset->id])) }}">Details</a>
                        @endpermission
                        @permission('media.delete')
                            <form method="POST" action="{{ route('tenant.media.destroy', $asset) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        @endpermission
                    </div>
                </div>
            </article>
        @empty
            <p class="text-sm text-slate-500">No files match this filter.</p>
        @endforelse
    </section>
    <div class="mt-4">{{ $assets->links() }}</div>
@endsection
