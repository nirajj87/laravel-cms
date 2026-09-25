<div class="space-y-4">
    @forelse ($grouped as $moduleSlug => $permissions)
        @php $moduleName = $modules[$moduleSlug]->name ?? str($moduleSlug)->headline(); @endphp
        <fieldset class="rounded-xl border border-stone-200 p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <legend class="text-sm font-semibold">{{ $moduleName }}</legend>
                @unless ($locked ?? false)
                    <label class="flex items-center gap-2 text-xs text-slate-500">
                        <input type="checkbox" class="rounded border-stone-300 text-teal-800"
                               onclick="document.querySelectorAll(`[data-module='{{ $moduleSlug }}']`).forEach((el) => { if (!el.disabled) el.checked = this.checked })">
                        All
                    </label>
                @endunless
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach ($permissions as $permission)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->slug }}" data-module="{{ $moduleSlug }}"
                               class="rounded border-stone-300 text-teal-800"
                               @checked(in_array($permission->slug, old('permissions', $selected ?? []), true))
                               @disabled($locked ?? false)>
                        {{ $permission->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @empty
        <p class="text-sm text-slate-500">Enable a module before assigning its permissions.</p>
    @endforelse
</div>
