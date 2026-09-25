@props(['label', 'name'])

<label class="mb-4 block">
    <span class="label">{{ $label }}</span>
    {{ $slot }}
    @error($name)
        <span class="mt-1 block text-sm text-red-700">{{ $message }}</span>
    @enderror
</label>
