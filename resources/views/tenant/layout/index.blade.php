@extends('layouts.app')

@section('title', 'Layout')
@section('kicker', 'Design')
@section('heading', 'Layout')

@section('content')
    <p class="mb-4 text-sm text-slate-500">Each row is a 4-column grid on desktop, 2 on tablet, and 1 on a phone. Spans are limited to those columns.</p>
    @foreach (['header' => 'Header', 'main' => 'Main', 'footer' => 'Footer'] as $region => $label)
        <section class="card mb-6 p-5">
            <h2 class="mb-4 font-semibold">{{ $label }}</h2>
            @foreach ($blocks->where('region', $region) as $block)
                <form method="POST" action="{{ route('tenant.layout.update', $block) }}" class="mb-4 grid gap-2 border-b border-stone-100 pb-4 lg:grid-cols-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="region" value="{{ $block->region }}">
                    <select class="field" name="component">
                        @foreach ($components[$region] as $component)
                            <option value="{{ $component }}" @selected($block->component === $component)>{{ str_replace('_', ' ', $component) }}</option>
                        @endforeach
                    </select>
                    <input class="field" type="number" name="row" min="1" value="{{ $block->row }}" title="Row">
                    <input class="field" type="number" name="sort_order" min="0" value="{{ $block->sort_order }}" title="Order">
                    <input class="field" type="number" name="col_desktop" min="1" max="4" value="{{ $block->col_desktop }}" title="Desktop columns">
                    <input class="field" type="number" name="col_tablet" min="1" max="2" value="{{ $block->col_tablet }}" title="Tablet columns">
                    <input type="hidden" name="col_mobile" value="1">
                    <input type="hidden" name="enabled" value="0">
                    <label class="text-sm"><input type="checkbox" name="enabled" value="1" @checked($block->enabled)> Enabled</label>
                    @if ($block->component === 'custom_text' || $block->component === 'hero')
                        <input class="field lg:col-span-3" name="heading" value="{{ $block->settings['heading'] ?? '' }}" placeholder="Heading">
                        <textarea class="field lg:col-span-3" name="body" rows="2" placeholder="Safe text">{{ $block->settings['body'] ?? '' }}</textarea>
                    @endif
                    <button class="btn btn-secondary" type="submit">Save</button>
                </form>
                <form method="POST" action="{{ route('tenant.layout.destroy', $block) }}" class="-mt-2 mb-4">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Remove</button>
                </form>
            @endforeach
            @permission('layout-builder.create')
                <form method="POST" action="{{ route('tenant.layout.store') }}" class="grid gap-2 lg:grid-cols-4">
                    @csrf
                    <input type="hidden" name="region" value="{{ $region }}">
                    <select class="field" name="component">
                        @foreach ($components[$region] as $component)
                            <option value="{{ $component }}">{{ str_replace('_', ' ', $component) }}</option>
                        @endforeach
                    </select>
                    <input class="field" type="number" name="row" value="1" min="1">
                    <input class="field" type="number" name="col_desktop" value="4" min="1" max="4">
                    <input type="hidden" name="col_tablet" value="2">
                    <input type="hidden" name="col_mobile" value="1">
                    <button class="btn btn-primary" type="submit">Add section</button>
                </form>
            @endpermission
        </section>
    @endforeach
@endsection
