@extends('layouts.app')

@section('title', 'Feedback')
@section('kicker', 'Growth')
@section('heading', 'Feedback')

@section('content')
    <form method="POST" action="{{ route('tenant.feedback.settings') }}" class="card mb-6 grid gap-4 p-5 lg:grid-cols-2">
        @csrf
        @method('PUT')
        <h2 class="font-semibold lg:col-span-2">Spam protection</h2>
        <p class="text-sm text-slate-600 lg:col-span-2">Public forms already use CSRF, validation, a honeypot, and rate limits. Captcha stays off until a provider and secret are saved. The secret is encrypted and is not shown again.</p>
        <input type="hidden" name="captcha_enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="captcha_enabled" value="1" @checked($captcha['enabled'])> Require captcha</label>
        <x-field label="Provider" name="captcha_provider">
            <select class="field" name="captcha_provider">
                @foreach (config('growth.captcha_providers') as $value => $label)
                    <option value="{{ $value }}" @selected($captcha['provider'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Site key" name="captcha_site_key"><input class="field" name="captcha_site_key" value="{{ $captcha['site_key'] }}"></x-field>
        <x-field label="Secret" name="captcha_secret"><input class="field" type="password" name="captcha_secret" autocomplete="new-password" placeholder="{{ $captcha['secret_set'] ? 'Saved secret kept if blank' : 'Not set' }}"></x-field>
        <div class="lg:col-span-2"><button class="btn btn-secondary" type="submit">Save protection</button></div>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-stone-50 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">From</th>
                    <th class="px-4 py-3 font-medium">Rating</th>
                    <th class="px-4 py-3 font-medium">Comment</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($feedback as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $item->name }}</p>
                            <p class="text-xs text-slate-500">{{ $item->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $item->rating }}/5</td>
                        <td class="px-4 py-3 max-w-xs">{{ $item->comment }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('tenant.feedback.update', $item) }}">
                                @csrf
                                @method('PUT')
                                <select class="field" name="status" onchange="this.form.requestSubmit()">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($item->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('tenant.feedback.destroy', $item) }}" onsubmit="return confirm('Delete this feedback?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-700" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No feedback yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $feedback->links() }}</div>
@endsection
