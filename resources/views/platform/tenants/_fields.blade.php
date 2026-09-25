<x-field label="Tenant name" name="name">
    <input class="field" name="name" value="{{ old('name', $tenant->name ?? '') }}" required>
</x-field>
<x-field label="Slug" name="slug">
    <input class="field" name="slug" value="{{ old('slug', $tenant->slug ?? '') }}" required>
</x-field>
<div class="grid gap-4 sm:grid-cols-2">
    <x-field label="Domain" name="domain">
        <input class="field" name="domain" value="{{ old('domain', $tenant->domain ?? '') }}" placeholder="acme.example.com">
    </x-field>
    <x-field label="Subdomain" name="subdomain">
        <input class="field" name="subdomain" value="{{ old('subdomain', $tenant->subdomain ?? '') }}" placeholder="acme">
    </x-field>
</div>
<x-field label="Email" name="email">
    <input class="field" type="email" name="email" value="{{ old('email', $tenant->email ?? '') }}">
</x-field>
<x-field label="Phone" name="phone">
    <input class="field" name="phone" value="{{ old('phone', $tenant->phone ?? '') }}">
</x-field>
<x-field label="Address" name="address">
    <textarea class="field" name="address" rows="3">{{ old('address', $tenant->address ?? '') }}</textarea>
</x-field>
<x-field label="Status" name="status">
    <select class="field" name="status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', isset($tenant) ? $tenant->status->value : 'active') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
</x-field>
<div class="grid gap-4 sm:grid-cols-2">
    <x-field label="Logo" name="logo">
        <input class="field" type="file" name="logo" accept="image/*">
    </x-field>
    <x-field label="Favicon" name="favicon">
        <input class="field" type="file" name="favicon" accept="image/*">
    </x-field>
</div>
