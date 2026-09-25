@extends('layouts.app')

@section('title', $post->exists ? 'Edit post' : 'Add post')
@section('kicker', 'Content')
@section('heading', $post->exists ? $post->title : 'Add post')

@section('content')
    @if (! $type)
        <section class="card p-5">
            <h2 class="mb-1 font-semibold">Choose a category first</h2>
            <p class="mb-4 text-sm text-slate-500">Fields open after you pick a category. The matching content type is selected for you.</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($categoryTree as $parent)
                    <div class="rounded-xl border border-stone-200 p-4">
                        <a class="block font-medium text-teal-900 hover:underline" href="{{ route('tenant.posts.create', ['category' => $parent->id]) }}">{{ $parent->name }}</a>
                        @if ($parent->children->isNotEmpty())
                            <ul class="mt-3 space-y-1.5 border-t border-stone-100 pt-3">
                                @foreach ($parent->children as $child)
                                    <li>
                                        <a class="text-sm text-slate-700 hover:text-teal-800" href="{{ route('tenant.posts.create', ['category' => $child->id]) }}">{{ $child->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Create categories before adding a post.</p>
                @endforelse
            </div>
            @if ($types->isNotEmpty())
                <details class="mt-6">
                    <summary class="cursor-pointer text-sm font-medium text-slate-600">Or pick a content type directly</summary>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($types as $choice)
                            <a class="rounded-xl border border-stone-200 p-4 hover:border-teal-700" href="{{ route('tenant.posts.create', ['type' => $choice->id]) }}">
                                <p class="font-medium">{{ $choice->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $choice->description }}</p>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        </section>
    @else
        @php
            $titleField = $type->fields->firstWhere('key', 'title');
            $categoryField = $type->fields->first(fn ($field) => $field->enabled && $field->type === \App\Enums\FieldType::Category);
            $selectedIds = array_map('intval', (array) old('categories', $selectedCategory?->id ? [$selectedCategory->id] : ($post->exists ? $post->categories->pluck('id')->all() : [])));
            $hasCategory = $selectedIds !== [];
        @endphp
        <form method="POST"
              action="{{ $post->exists ? route('tenant.posts.update', $post) : route('tenant.posts.store') }}"
              enctype="multipart/form-data"
              class="grid gap-6 lg:grid-cols-[1fr_280px]"
              x-data="{ ready: {{ $hasCategory || $post->exists ? 'true' : 'false' }} }">
            @csrf
            @if ($post->exists)
                @method('PUT')
            @else
                <input type="hidden" name="content_type_id" value="{{ $type->id }}">
            @endif
            <section class="card p-5">
                <p class="mb-4 text-sm text-slate-500">{{ $type->name }}@if($selectedCategory) · {{ $selectedCategory->name }}@endif</p>

                @if ($categoryField)
                    <fieldset class="mb-5">
                        <legend class="label">{{ $categoryField->label }}</legend>
                        <p class="mb-2 text-xs text-slate-500">Select a category to unlock the rest of the fields.</p>
                        <div class="max-h-56 space-y-2 overflow-auto rounded-lg border border-stone-200 p-3">
                            @forelse ($categories as $category)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox"
                                           name="categories[]"
                                           value="{{ $category->id }}"
                                           @checked(in_array($category->id, $selectedIds, true))
                                           @change="ready = [...$el.form.querySelectorAll('input[name=\'categories[]\']:checked')].length > 0">
                                    {{ $category->name }}
                                </label>
                            @empty
                                <p class="text-sm text-slate-500">No categories yet.</p>
                            @endforelse
                        </div>
                        @error('categories')
                            <span class="mt-1 block text-sm text-red-700">{{ $message }}</span>
                        @enderror
                    </fieldset>
                @else
                    <template x-init="ready = true"></template>
                @endif

                <div x-show="ready" x-cloak x-transition class="space-y-1">
                    <x-field :label="$titleField->label ?? 'Title'" name="title">
                        <input class="field" name="title" value="{{ old('title', $post->title) }}" required>
                    </x-field>
                    @if ($post->exists)
                        <x-field label="Slug" name="slug">
                            <input class="field" name="slug" value="{{ old('slug', $post->slug) }}">
                        </x-field>
                    @endif
                    @foreach ($type->fields as $field)
                        @continue(! $field->enabled || $field->key === 'title' || $field->type === \App\Enums\FieldType::Category)
                        @include('tenant.posts._fields', ['field' => $field, 'post' => $post, 'categories' => $categories, 'library' => $library])
                    @endforeach
                </div>

                <p x-show="!ready" class="rounded-lg border border-dashed border-stone-300 bg-stone-50 px-4 py-6 text-sm text-slate-600">
                    Pick at least one category above to show the fields for this post.
                </p>
            </section>
            <aside class="card h-fit p-5" x-show="ready" x-cloak>
                <x-field label="Status" name="status">
                    <select class="field" name="status">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $post->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Publish at" name="scheduled_at">
                    <input class="field" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}">
                </x-field>
                <h2 class="mb-3 mt-6 text-sm font-semibold">SEO</h2>
                <x-field label="SEO title" name="seo_title">
                    <input class="field" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}">
                </x-field>
                <x-field label="Meta description" name="seo_description">
                    <textarea class="field" name="seo_description" rows="3">{{ old('seo_description', $post->seo_description) }}</textarea>
                </x-field>
                <x-field label="Keywords" name="seo_keywords">
                    <input class="field" name="seo_keywords" value="{{ old('seo_keywords', $post->seo_keywords) }}">
                </x-field>
                <x-field label="Canonical URL" name="canonical_url">
                    <input class="field" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}" placeholder="https://">
                </x-field>
                <x-field label="OG image" name="og_image_id">
                    <select class="field" name="og_image_id">
                        <option value="">None</option>
                        @foreach ($library as $image)
                            <option value="{{ $image->id }}" @selected((int) old('og_image_id', $post->og_image_id) === $image->id)>{{ $image->original_name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <button class="btn btn-primary w-full" type="submit">{{ $post->exists ? 'Save post' : 'Create post' }}</button>
                @if ($post->exists)
                    <a class="btn btn-secondary mt-2 w-full" href="{{ route('tenant.posts.preview', $post) }}">Preview</a>
                    @permission('posts.delete')
                        <button class="btn btn-danger mt-2 w-full" type="submit" form="delete-post">Delete</button>
                    @endpermission
                @endif
            </aside>
        </form>
        @if ($post->exists)
            <form id="delete-post" method="POST" action="{{ route('tenant.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endif
@endsection
