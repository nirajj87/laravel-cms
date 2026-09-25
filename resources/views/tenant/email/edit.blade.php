@extends('layouts.app')

@section('title', 'Email')
@section('kicker', 'Growth')
@section('heading', 'Email')

@section('content')
    <form method="POST" action="{{ route('tenant.email.update') }}" class="grid gap-6">
        @csrf
        @method('PUT')
        <section class="card grid gap-4 p-5 lg:grid-cols-2">
            <h2 class="font-semibold lg:col-span-2">SMTP</h2>
            <p class="text-sm text-slate-600 lg:col-span-2">The password is encrypted and is not shown again. Leave it blank to keep the saved password.</p>
            <x-field label="Host" name="host"><input class="field" name="host" value="{{ old('host', $email['host']) }}"></x-field>
            <x-field label="Port" name="port"><input class="field" type="number" name="port" value="{{ old('port', $email['port']) }}" min="1" max="65535"></x-field>
            <x-field label="Username" name="username"><input class="field" name="username" value="{{ old('username', $email['username']) }}" autocomplete="off"></x-field>
            <x-field label="Password" name="password"><input class="field" type="password" name="password" autocomplete="new-password" placeholder="{{ $email['password_set'] ? 'Saved password kept if blank' : 'Not set' }}"></x-field>
            <x-field label="Encryption" name="encryption">
                <select class="field" name="encryption">
                    @foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('encryption', $email['encryption']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="From name" name="from_name"><input class="field" name="from_name" value="{{ old('from_name', $email['from_name']) }}"></x-field>
            <x-field label="From email" name="from_email"><input class="field" type="email" name="from_email" value="{{ old('from_email', $email['from_email']) }}"></x-field>
        </section>
        @foreach ($templates as $template)
            <section class="card grid gap-3 p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">{{ $template->name }}</h2>
                    <input type="hidden" name="templates[{{ $template->id }}][enabled]" value="0">
                    <label class="text-sm"><input type="checkbox" name="templates[{{ $template->id }}][enabled]" value="1" @checked(old('templates.'.$template->id.'.enabled', $template->enabled))> Enabled</label>
                </div>
                <x-field label="Subject" name="templates.{{ $template->id }}.subject"><input class="field" name="templates[{{ $template->id }}][subject]" value="{{ old('templates.'.$template->id.'.subject', $template->subject) }}"></x-field>
                <x-field label="Body" name="templates.{{ $template->id }}.body"><textarea class="field" name="templates[{{ $template->id }}][body]" rows="4">{{ old('templates.'.$template->id.'.body', $template->body) }}</textarea></x-field>
                <p class="text-xs text-slate-500">Placeholders: @{{name}}, @{{email}}, @{{message}}, @{{tenant}}, @{{rating}}, @{{comment}}, @{{subject}}</p>
            </section>
        @endforeach
        <button class="btn btn-primary w-fit" type="submit">Save email</button>
    </form>
@endsection
