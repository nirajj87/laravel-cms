@extends('layouts.app')

@section('title', $category->exists ? 'Edit category' : 'Add category')
@section('kicker', 'Content')
@section('heading', $category->exists ? $category->name : 'Add category')

@section('content')
    <form method="POST" action="{{ $category->exists ? route('tenant.categories.update', $category) : route('tenant.categories.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-2">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif
        <section class="card p-5">
            <x-field label="Name" name="name">
                <input class="field" name="name" value="{{ old('name', $category->name) }}" required>
            </x-field>
            <x-field label="Slug" name="slug">
                <input class="field" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="Generated from the name">
            </x-field>
            <x-field label="Description" name="description">
                <textarea class="field" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
            </x-field>
            <x-field label="Parent category" name="parent_id">
                <select class="field" name="parent_id">
                    <option value="">None</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </x-field>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Status" name="status">
                    <select class="field" name="status">
                        @foreach (\App\Enums\CategoryStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $category->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Sort order" name="sort_order">
                    <input class="field" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                </x-field>
            </div>
            <x-field label="Image" name="image">
                @if ($category->image)
                    <img src="{{ $category->image->url() }}" alt="{{ $category->image->alt ?: $category->name }}" class="mb-3 h-24 w-24 rounded-lg object-cover">
                    <label class="mb-3 flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remove_image" value="1">
                        Remove image
                    </label>
                @endif
                <input class="field" type="file" name="image" accept="image/*">
            </x-field>
            <button class="btn btn-primary" type="submit">{{ $category->exists ? 'Save category' : 'Create category' }}</button>
        </section>
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Search</h2>
            <x-field label="SEO title" name="seo_title">
                <input class="field" name="seo_title" value="{{ old('seo_title', $category->seo_title) }}">
            </x-field>
            <x-field label="SEO description" name="seo_description">
                <textarea class="field" name="seo_description" rows="3">{{ old('seo_description', $category->seo_description) }}</textarea>
            </x-field>
            <x-field label="SEO keywords" name="seo_keywords">
                <input class="field" name="seo_keywords" value="{{ old('seo_keywords', $category->seo_keywords) }}">
            </x-field>
        </section>
    </form>
@endsection
