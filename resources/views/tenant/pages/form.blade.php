@extends('layouts.app')

@section('title', $page->exists ? 'Edit page' : 'Add page')
@section('kicker', 'Content')
@section('heading', $page->exists ? $page->title : 'Add page')

@section('content')
    <form method="POST" action="{{ $page->exists ? route('tenant.pages.update', $page) : route('tenant.pages.store') }}" class="card max-w-3xl p-5">
        @csrf
        @if ($page->exists)
            @method('PUT')
        @endif
        <x-field label="Title" name="title">
            <input class="field" name="title" value="{{ old('title', $page->title) }}" required>
        </x-field>
        <x-field label="Slug" name="slug">
            <input class="field" name="slug" value="{{ old('slug', $page->slug) }}">
        </x-field>
        <x-field label="Body" name="body">
            <textarea class="field" name="body" rows="8">{{ old('body', $page->body) }}</textarea>
        </x-field>
        <x-field label="Status" name="status">
            <select class="field" name="status">
                <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
            </select>
        </x-field>
        <x-field label="SEO title" name="seo_title">
            <input class="field" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}">
        </x-field>
        <x-field label="SEO description" name="seo_description">
            <textarea class="field" name="seo_description" rows="2">{{ old('seo_description', $page->seo_description) }}</textarea>
        </x-field>
        <button class="btn btn-primary" type="submit">Save page</button>
    </form>
    @if ($page->exists)
        @permission('pages.delete')
            <form method="POST" action="{{ route('tenant.pages.destroy', $page) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Delete page</button>
            </form>
        @endpermission
    @endif
@endsection
