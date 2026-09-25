@php
    $selected = $enabled ?? [];
@endphp

<div class="space-y-6">
    @foreach ($groups as $group => $label)
        @if (($modules[$group] ?? collect())->isNotEmpty())
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-slate-900">{{ $label }}</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($modules[$group] as $module)
                        <label class="flex gap-3 rounded-xl border border-stone-200 px-3 py-3 {{ $module->is_core ? 'bg-stone-50' : 'bg-white' }}">
                            <input type="checkbox" name="modules[]" value="{{ $module->slug }}" class="mt-1 rounded border-stone-300 text-teal-800"
                                   @checked($module->is_core || in_array($module->slug, old('modules', $selected), true))
                                   @disabled($module->is_core)>
                            @if ($module->is_core)
                                <input type="hidden" name="modules[]" value="{{ $module->slug }}">
                            @endif
                            <span>
                                <span class="block text-sm font-medium">{{ $module->name }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $module->description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
    @endforeach
</div>
