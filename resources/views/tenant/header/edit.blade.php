@extends('layouts.app')

@section('title', 'Header and footer')
@section('kicker', 'Design')
@section('heading', 'Header and footer')

@section('content')
    <form method="POST" action="{{ route('tenant.header.update') }}" class="grid gap-6 lg:grid-cols-2">
        @csrf
        @method('PUT')
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Header</h2>
            <x-field label="Logo size" name="logo_size">
                <select class="field" name="logo_size">
                    @foreach (config('site.logo_sizes') as $size => $pixels)
                        <option value="{{ $size }}" @selected($site['logo_size'] === $size)>{{ ucfirst($size) }} ({{ $pixels }}px)</option>
                    @endforeach
                </select>
            </x-field>
            <label class="mb-4 flex items-center gap-2 text-sm"><input type="hidden" name="show_login" value="0"><input type="checkbox" name="show_login" value="1" @checked($site['show_login'])> Login</label>
            <label class="mb-4 flex items-center gap-2 text-sm"><input type="hidden" name="show_search" value="0"><input type="checkbox" name="show_search" value="1" @checked($site['show_search'])> Search</label>
            <x-field label="CTA label" name="cta_label">
                <input class="field" name="cta_label" value="{{ $site['cta_label'] }}">
            </x-field>
            <x-field label="CTA URL" name="cta_url">
                <input class="field" name="cta_url" value="{{ $site['cta_url'] }}">
            </x-field>
            <label class="mb-4 flex items-center gap-2 text-sm"><input type="hidden" name="cta_new_tab" value="0"><input type="checkbox" name="cta_new_tab" value="1" @checked($site['cta_new_tab'])> Open CTA in a new tab</label>
            <x-field label="Custom header HTML" name="custom_header">
                <textarea class="field" name="custom_header" rows="4">{{ $site['custom_header'] }}</textarea>
            </x-field>
        </section>
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Footer</h2>
            <x-field label="Description" name="footer_description">
                <textarea class="field" name="footer_description" rows="3">{{ $site['footer_description'] }}</textarea>
            </x-field>
            <x-field label="Copyright" name="copyright">
                <input class="field" name="copyright" value="{{ $site['copyright'] }}">
            </x-field>
            <x-field label="Contact email" name="contact_email">
                <input class="field" name="contact_email" value="{{ $site['contact_email'] }}">
            </x-field>
            <x-field label="Phone" name="contact_phone">
                <input class="field" name="contact_phone" value="{{ $site['contact_phone'] }}">
            </x-field>
            <x-field label="Address" name="contact_address">
                <textarea class="field" name="contact_address" rows="2">{{ $site['contact_address'] }}</textarea>
            </x-field>
            <label class="mb-4 flex items-center gap-2 text-sm"><input type="hidden" name="newsletter" value="0"><input type="checkbox" name="newsletter" value="1" @checked($site['newsletter'])> Newsletter</label>
            <x-field label="Newsletter heading" name="newsletter_heading">
                <input class="field" name="newsletter_heading" value="{{ $site['newsletter_heading'] }}">
            </x-field>
            <h3 class="mb-2 font-medium">Social links</h3>
            @foreach ($platforms as $index => $platform)
                @php $current = collect($site['social'])->firstWhere('platform', $platform); @endphp
                <div class="mb-2 grid grid-cols-[8rem_1fr] gap-2">
                    <input type="hidden" name="social[{{ $index }}][platform]" value="{{ $platform }}">
                    <span class="label">{{ ucfirst($platform) }}</span>
                    <input class="field" name="social[{{ $index }}][url]" value="{{ $current['url'] ?? '' }}" placeholder="https://">
                </div>
            @endforeach
            <x-field label="Custom footer HTML" name="custom_footer">
                <textarea class="field" name="custom_footer" rows="4">{{ $site['custom_footer'] }}</textarea>
            </x-field>
            @permission('header-footer.update')
                <button class="btn btn-primary" type="submit">Save header and footer</button>
            @endpermission
        </section>
    </form>
@endsection
