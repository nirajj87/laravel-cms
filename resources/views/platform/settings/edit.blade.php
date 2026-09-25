@extends('layouts.app')

@section('title', 'Platform settings')
@section('kicker', 'Platform')
@section('heading', 'Settings')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach (['general' => 'General', 'email' => 'Email', 'seo' => 'SEO', 'system' => 'System'] as $id => $label)
            <a class="btn btn-secondary" href="#{{ $id }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="grid gap-6">
        <form id="general" method="POST" action="{{ route('platform.settings.update', 'general') }}" class="card p-5">
            @csrf
            @method('PUT')
            <h2 class="mb-4 font-semibold">General</h2>
            <x-field label="Platform name" name="platform_name">
                <input class="field" name="platform_name" value="{{ old('platform_name', $values['general']['platform_name'] ?? config('app.name')) }}" required>
            </x-field>
            <x-field label="Tagline" name="tagline">
                <input class="field" name="tagline" value="{{ old('tagline', $values['general']['tagline'] ?? '') }}">
            </x-field>
            <button class="btn btn-primary" type="submit">Save general</button>
        </form>

        <form id="email" method="POST" action="{{ route('platform.settings.update', 'email') }}" class="card p-5">
            @csrf
            @method('PUT')
            <h2 class="mb-4 font-semibold">Email</h2>
            <x-field label="Mailer" name="mailer">
                <select class="field" name="mailer">
                    @foreach (['log' => 'Log (local)', 'smtp' => 'SMTP'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('mailer', $values['email']['mailer'] ?? 'log') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="SMTP host" name="host">
                    <input class="field" name="host" value="{{ old('host', $values['email']['host'] ?? '') }}">
                </x-field>
                <x-field label="SMTP port" name="port">
                    <input class="field" type="number" name="port" value="{{ old('port', $values['email']['port'] ?? 587) }}">
                </x-field>
            </div>
            <x-field label="SMTP username" name="username">
                <input class="field" name="username" value="{{ old('username', $values['email']['username'] ?? '') }}">
            </x-field>
            <x-field label="SMTP password" name="password">
                <input class="field" type="password" name="password" placeholder="Leave blank to keep the current password" autocomplete="new-password">
            </x-field>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="From address" name="from_address">
                    <input class="field" type="email" name="from_address" value="{{ old('from_address', $values['email']['from_address'] ?? 'hello@example.com') }}" required>
                </x-field>
                <x-field label="From name" name="from_name">
                    <input class="field" name="from_name" value="{{ old('from_name', $values['email']['from_name'] ?? config('app.name')) }}" required>
                </x-field>
            </div>
            <button class="btn btn-primary" type="submit">Save email</button>
        </form>

        <form id="seo" method="POST" action="{{ route('platform.settings.update', 'seo') }}" class="card p-5">
            @csrf
            @method('PUT')
            <h2 class="mb-4 font-semibold">SEO defaults</h2>
            <x-field label="Default title" name="default_title">
                <input class="field" name="default_title" value="{{ old('default_title', $values['seo']['default_title'] ?? '') }}">
            </x-field>
            <x-field label="Default description" name="default_description">
                <textarea class="field" name="default_description" rows="3">{{ old('default_description', $values['seo']['default_description'] ?? '') }}</textarea>
            </x-field>
            <x-field label="Default keywords" name="default_keywords">
                <input class="field" name="default_keywords" value="{{ old('default_keywords', $values['seo']['default_keywords'] ?? '') }}">
            </x-field>
            <x-field label="Robots" name="robots">
                <input class="field" name="robots" value="{{ old('robots', $values['seo']['robots'] ?? 'index,follow') }}" required>
            </x-field>
            <button class="btn btn-primary" type="submit">Save SEO</button>
        </form>

        <form id="system" method="POST" action="{{ route('platform.settings.update', 'system') }}" class="card p-5">
            @csrf
            @method('PUT')
            <h2 class="mb-4 font-semibold">System</h2>
            <x-field label="Support email" name="support_email">
                <input class="field" type="email" name="support_email" value="{{ old('support_email', $values['system']['support_email'] ?? '') }}">
            </x-field>
            <x-field label="Default timezone" name="default_timezone">
                <input class="field" name="default_timezone" value="{{ old('default_timezone', $values['system']['default_timezone'] ?? 'UTC') }}" required>
            </x-field>
            <x-field label="Backup retention" name="backup_retention">
                <input class="field" type="number" min="1" max="100" name="backup_retention" value="{{ old('backup_retention', $values['system']['backup_retention'] ?? 10) }}" required>
            </x-field>
            <button class="btn btn-primary" type="submit">Save system</button>
        </form>
    </div>
@endsection
