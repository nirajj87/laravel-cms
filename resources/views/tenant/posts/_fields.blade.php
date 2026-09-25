@php
    $value = old('fields.'.$field->key, $post->exists ? $post->valueFor($field) : null);
    $typeName = $field->type;
@endphp

@if ($typeName === \App\Enums\FieldType::Category)
    <fieldset class="mb-4">
        <legend class="label">{{ $field->label }}</legend>
        <div class="max-h-56 space-y-2 overflow-auto rounded-lg border border-stone-200 p-3">
            @forelse ($categories as $category)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array($category->id, array_map('intval', (array) old('categories', $post->exists ? $post->categories->pluck('id')->all() : []))))>
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
@elseif ($typeName === \App\Enums\FieldType::DetailPage)
    {{-- Detail page is a type setting, not a post value. --}}
@elseif ($typeName === \App\Enums\FieldType::Button)
    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <x-field :label="$field->label.' URL'" :name="'fields.'.$field->key.'.url'">
            <input class="field" type="url" name="fields[{{ $field->key }}][url]" value="{{ old('fields.'.$field->key.'.url', is_array($value) ? ($value['url'] ?? '') : '') }}">
        </x-field>
        <x-field :label="$field->label.' label'" :name="'fields.'.$field->key.'.label'">
            <input class="field" name="fields[{{ $field->key }}][label]" value="{{ old('fields.'.$field->key.'.label', is_array($value) ? ($value['label'] ?? $field->label) : $field->label) }}">
        </x-field>
        <x-field label="Button style" :name="'fields.'.$field->key.'.style'">
            <select class="field" name="fields[{{ $field->key }}][style]">
                @foreach (config('site.button_styles') as $style => $styleLabel)
                    <option value="{{ $style }}" @selected(old('fields.'.$field->key.'.style', is_array($value) ? ($value['style'] ?? 'primary') : 'primary') === $style)>{{ $styleLabel }}</option>
                @endforeach
            </select>
        </x-field>
        <label class="mb-4 flex items-center gap-2 text-sm font-medium">
            <input type="hidden" name="fields[{{ $field->key }}][new_tab]" value="0">
            <input type="checkbox" name="fields[{{ $field->key }}][new_tab]" value="1" @checked(old('fields.'.$field->key.'.new_tab', is_array($value) ? ($value['new_tab'] ?? false) : false))>
            Open in a new tab
        </label>
    </div>
@elseif ($typeName === \App\Enums\FieldType::Tags)
    <x-field :label="$field->label" :name="'fields.'.$field->key">
        <input class="field" name="fields[{{ $field->key }}]" value="{{ is_array($value) ? implode(', ', $value) : (is_string($value) ? $value : '') }}" placeholder="Comma separated">
    </x-field>
@elseif ($typeName === \App\Enums\FieldType::LongText || $typeName === \App\Enums\FieldType::RichText)
    <x-field :label="$field->label" :name="'fields.'.$field->key">
        <textarea class="field" name="fields[{{ $field->key }}]" rows="{{ $typeName === \App\Enums\FieldType::RichText ? 8 : 4 }}">{{ is_string($value) ? $value : '' }}</textarea>
    </x-field>
@elseif (in_array($typeName, [\App\Enums\FieldType::Image, \App\Enums\FieldType::File], true))
    <x-field :label="$field->label" :name="'fields.'.$field->key">
        @if (is_numeric($value))
            <input type="hidden" name="fields[{{ $field->key }}]" value="{{ $value }}">
            <label class="mb-2 flex items-center gap-2 text-sm">
                <input type="checkbox" name="remove[{{ $field->key }}]" value="1">
                Remove current file
            </label>
        @endif
        @if ($typeName === \App\Enums\FieldType::Image && $library->isNotEmpty())
            <select class="field mb-2" name="fields[{{ $field->key }}]">
                <option value="">Choose from the library</option>
                @foreach ($library as $asset)
                    <option value="{{ $asset->id }}" @selected((string) $value === (string) $asset->id)>{{ $asset->original_name }}</option>
                @endforeach
            </select>
        @endif
        <input class="field" type="file" name="uploads[{{ $field->key }}]" @if ($typeName === \App\Enums\FieldType::Image) accept="image/*" @endif>
    </x-field>
@elseif ($typeName === \App\Enums\FieldType::Gallery)
    <x-field :label="$field->label" name="fields.{{ $field->key }}">
        @foreach ((array) $value as $id)
            <input type="hidden" name="fields[{{ $field->key }}][]" value="{{ $id }}">
        @endforeach
        @if ($value)
            <label class="mb-2 flex items-center gap-2 text-sm">
                <input type="checkbox" name="remove[{{ $field->key }}]" value="1">
                Replace the current gallery
            </label>
        @endif
        <input class="field" type="file" name="uploads[{{ $field->key }}][]" accept="image/*" multiple>
    </x-field>
@elseif ($typeName === \App\Enums\FieldType::Boolean || ($typeName === \App\Enums\FieldType::Checkbox && empty($field->options)))
    <label class="mb-4 flex items-center gap-2 text-sm font-medium">
        <input type="hidden" name="fields[{{ $field->key }}]" value="0">
        <input type="checkbox" name="fields[{{ $field->key }}]" value="1" @checked((string) $value === '1')>
        {{ $field->label }}
    </label>
@elseif (in_array($typeName, [\App\Enums\FieldType::Select, \App\Enums\FieldType::Radio, \App\Enums\FieldType::MultiSelect, \App\Enums\FieldType::Checkbox], true))
    <x-field :label="$field->label" :name="'fields.'.$field->key">
        @if ($typeName === \App\Enums\FieldType::Select)
            <select class="field" name="fields[{{ $field->key }}]">
                <option value="">Choose</option>
                @foreach ($field->options ?? [] as $option)
                    <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
                @endforeach
            </select>
        @else
            <div class="space-y-2">
                @foreach ($field->options ?? [] as $option)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="{{ $typeName === \App\Enums\FieldType::Radio ? 'radio' : 'checkbox' }}" name="fields[{{ $field->key }}]{{ $typeName === \App\Enums\FieldType::Radio ? '' : '[]' }}" value="{{ $option }}" @checked(in_array($option, (array) $value, true))>
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        @endif
    </x-field>
@elseif ($typeName === \App\Enums\FieldType::Color)
    <x-field :label="$field->label" :name="'fields.'.$field->key">
        <input class="field" type="color" name="fields[{{ $field->key }}]" value="{{ is_string($value) && $value !== '' ? $value : '#0f766e' }}">
    </x-field>
@else
    @php
        $inputType = match ($typeName) {
            \App\Enums\FieldType::Email => 'email',
            \App\Enums\FieldType::Date => 'date',
            \App\Enums\FieldType::DateTime => 'datetime-local',
            \App\Enums\FieldType::Number, \App\Enums\FieldType::Currency, \App\Enums\FieldType::Price, \App\Enums\FieldType::Discount, \App\Enums\FieldType::Rating => 'number',
            \App\Enums\FieldType::Url, \App\Enums\FieldType::VideoUrl, \App\Enums\FieldType::ExternalLink => 'url',
            default => 'text',
        };
    @endphp
    <x-field :label="$field->label" :name="'fields.'.$field->key">
        <input class="field" type="{{ $inputType }}" name="fields[{{ $field->key }}]" value="{{ is_scalar($value) ? $value : '' }}" @if ($typeName === \App\Enums\FieldType::Rating) min="0" max="5" step="0.1" @endif @if (in_array($typeName, [\App\Enums\FieldType::Number, \App\Enums\FieldType::Currency, \App\Enums\FieldType::Price, \App\Enums\FieldType::Discount], true)) step="0.01" @endif>
    </x-field>
@endif
