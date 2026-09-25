@extends('public.layout')

@section('content')
    <h1 class="text-3xl font-semibold">Contact {{ $tenant->name }}</h1>
    <form method="POST" action="{{ route('site.contact.store', ['siteTenant' => $tenant->slug]) }}" class="mt-6 max-w-xl">
        @csrf
        <label class="mb-3 block">Name
            <input class="site-input mt-1" name="name" value="{{ old('name') }}" required autocomplete="name">
        </label>
        <label class="mb-3 block">Email
            <input class="site-input mt-1" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </label>
        <label class="mb-3 block">Message
            <textarea class="site-input mt-1" name="message" rows="5" required>{{ old('message') }}</textarea>
        </label>
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label>Company <input name="company" tabindex="-1" autocomplete="off"></label>
        </div>
        @error('captcha')<p class="mb-3 text-sm text-red-700">{{ $message }}</p>@enderror
        <button class="site-btn" type="submit">Send</button>
    </form>
@endsection
