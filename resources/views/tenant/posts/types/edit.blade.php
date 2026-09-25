@extends('layouts.app')

@php
    $hub = $hub ?? 'tenant.posts.types';
    $isBuilder = $hub === 'tenant.form-builder';
@endphp

@section('title', $type->name)
@section('kicker', $isBuilder ? 'Form Builder' : 'Content types')
@section('heading', $type->name)

@section('content')
    <form method="POST" action="{{ route($hub.'.update', $type) }}" class="card mb-6 p-5">
        @csrf
        @method('PUT')
        <div class="grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
            <x-field label="Name" name="name">
                <input class="field" name="name" value="{{ old('name', $type->name) }}" required>
            </x-field>
            <x-field label="Description" name="description">
                <input class="field" name="description" value="{{ old('description', $type->description) }}">
            </x-field>
            <label class="mb-4 flex items-end gap-2 text-sm font-medium">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active))>
                Active
            </label>
        </div>
        <h2 class="mb-3 font-semibold">Enabled fields</h2>
        <p class="mb-4 text-sm text-slate-500">Posts and the public site only show fields that stay checked.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-2 pr-3">On</th>
                        <th class="py-2 pr-3">Label</th>
                        <th class="py-2 pr-3">Type</th>
                        <th class="py-2 pr-3">Required</th>
                        <th class="py-2 pr-3">Order</th>
                        <th class="py-2">Options</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($type->fields as $field)
                        <tr class="border-t border-stone-100">
                            <td class="py-3 pr-3">
                                <input type="hidden" name="fields[{{ $field->id }}][enabled]" value="0">
                                <input type="checkbox" name="fields[{{ $field->id }}][enabled]" value="1" @checked(old('fields.'.$field->id.'.enabled', $field->enabled)) @disabled($field->is_system)>
                            </td>
                            <td class="py-3 pr-3">
                                <input class="field" name="fields[{{ $field->id }}][label]" value="{{ old('fields.'.$field->id.'.label', $field->label) }}">
                                <span class="mt-1 block text-xs text-slate-400">{{ $field->key }}</span>
                            </td>
                            <td class="py-3 pr-3 text-slate-600">{{ $field->type->label() }}</td>
                            <td class="py-3 pr-3">
                                <input type="hidden" name="fields[{{ $field->id }}][required]" value="0">
                                <input type="checkbox" name="fields[{{ $field->id }}][required]" value="1" @checked(old('fields.'.$field->id.'.required', $field->required)) @disabled($field->key === 'title')>
                            </td>
                            <td class="py-3 pr-3">
                                <input class="field w-20" type="number" min="0" name="fields[{{ $field->id }}][sort_order]" value="{{ old('fields.'.$field->id.'.sort_order', $field->sort_order) }}">
                            </td>
                            <td class="py-3">
                                @if ($field->type->needsOptions() || $field->type === \App\Enums\FieldType::Checkbox)
                                    <input class="field" name="fields[{{ $field->id }}][options]" value="{{ old('fields.'.$field->id.'.options', implode(', ', $field->options ?? [])) }}" placeholder="Red, Blue">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">Save fields</button>
            <a class="btn btn-secondary" href="{{ route($hub.'.index') }}">Back</a>
        </div>
    </form>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route($hub.'.fields.store', $type) }}" class="card p-5">
            @csrf
            <h2 class="mb-4 font-semibold">Add a field</h2>
            <x-field label="Label" name="label">
                <input class="field" name="label" value="{{ old('label') }}" required>
            </x-field>
            <x-field label="Key" name="key">
                <input class="field" name="key" value="{{ old('key') }}" placeholder="optional_key" pattern="[a-z0-9_]*">
            </x-field>
            <x-field label="Type" name="type">
                <select class="field" name="type">
                    @foreach ($fieldTypes as $fieldType)
                        <option value="{{ $fieldType->value }}" @selected(old('type') === $fieldType->value)>{{ $fieldType->label() }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="Options" name="options">
                <input class="field" name="options" value="{{ old('options') }}" placeholder="Comma separated, for select fields">
            </x-field>
            <label class="mb-4 flex items-center gap-2 text-sm">
                <input type="checkbox" name="required" value="1" @checked(old('required'))>
                Required
            </label>
            <button class="btn btn-secondary" type="submit">Add field</button>
        </form>
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Remove a field</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($type->fields as $field)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $field->label }}</span>
                        @unless ($field->is_system)
                            <form method="POST" action="{{ route($hub.'.fields.destroy', [$type, $field]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Remove</button>
                            </form>
                        @endunless
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route($hub.'.destroy', $type) }}" class="mt-6" onsubmit="return confirm('Delete this form?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">{{ $isBuilder ? 'Delete form' : 'Delete content type' }}</button>
            </form>
            <p class="mt-3 text-sm text-slate-500">Posts using this form must be deleted first.</p>
        </section>
    </div>
@endsection
