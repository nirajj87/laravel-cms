@extends('layouts.app')

@section('title', 'Menus')
@section('kicker', 'Design')
@section('heading', 'Menus')

@section('content')
    @foreach ($menus as $menu)
        <section class="card mb-6 p-5">
            <h2 class="mb-4 font-semibold">{{ $menu->name }}</h2>
            @foreach ($menu->items as $item)
                <form method="POST" action="{{ route('tenant.menus.update', $item) }}" class="mb-3 grid gap-2 border-b border-stone-100 pb-3 lg:grid-cols-6">
                    @csrf
                    @method('PUT')
                    <input class="field" name="label" value="{{ $item->label }}">
                    <select class="field" name="type">
                        @foreach (['home' => 'Home', 'categories' => 'Categories', 'page' => 'Page', 'category' => 'Category', 'content_type' => 'Content type', 'url' => 'External URL', 'login' => 'Login'] as $value => $label)
                            <option value="{{ $value }}" @selected($item->type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input class="field" name="url" value="{{ $item->url }}" placeholder="https://">
                    <input class="field" name="target_id" value="{{ $item->target_id }}" placeholder="Page, category, or type id">
                    <input class="field" type="number" name="sort_order" value="{{ $item->sort_order }}">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="enabled" value="0">
                        <label class="text-sm"><input type="checkbox" name="enabled" value="1" @checked($item->enabled)> On</label>
                        <input type="hidden" name="open_new_tab" value="0">
                        <label class="text-sm"><input type="checkbox" name="open_new_tab" value="1" @checked($item->open_new_tab)> New tab</label>
                        <button class="btn btn-secondary" type="submit">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('tenant.menus.destroy', $item) }}" class="mb-4">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Remove {{ $item->label }}</button>
                </form>
            @endforeach
            @permission('menu-manager.create')
                <form method="POST" action="{{ route('tenant.menus.store') }}" class="grid gap-2 lg:grid-cols-4">
                    @csrf
                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                    <input class="field" name="label" placeholder="Label" required>
                    <select class="field" name="type">
                        <option value="home">Home</option>
                        <option value="page">Page</option>
                        <option value="category">Category</option>
                        <option value="content_type">Content type</option>
                        <option value="url">External URL</option>
                        <option value="login">Login</option>
                        <option value="categories">Categories</option>
                    </select>
                    <input class="field" name="url" placeholder="External URL">
                    <button class="btn btn-primary" type="submit">Add item</button>
                </form>
                <p class="mt-3 text-xs text-slate-500">Pages: @foreach ($pages as $page) {{ $page->title }} #{{ $page->id }} @endforeach</p>
                <p class="text-xs text-slate-500">Categories: @foreach ($categories as $category) {{ $category->name }} #{{ $category->id }} @endforeach</p>
                <p class="text-xs text-slate-500">Types: @foreach ($types as $type) {{ $type->name }} #{{ $type->id }} @endforeach</p>
            @endpermission
        </section>
    @endforeach
@endsection
