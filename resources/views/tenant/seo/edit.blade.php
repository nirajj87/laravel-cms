@extends('layouts.app')

@section('title', 'SEO')
@section('kicker', 'Growth')
@section('heading', 'SEO')

@section('content')
    <form method="POST" action="{{ route('tenant.seo.update') }}" class="card grid gap-4 p-5 lg:grid-cols-2">
        @csrf
        @method('PUT')
        <x-field label="Site title" name="site_title"><input class="field" name="site_title" value="{{ old('site_title', $seo['site_title']) }}"></x-field>
        <x-field label="Canonical URL" name="canonical_url"><input class="field" name="canonical_url" value="{{ old('canonical_url', $seo['canonical_url']) }}" placeholder="https://"></x-field>
        <x-field label="Meta description" name="meta_description"><textarea class="field" name="meta_description" rows="3">{{ old('meta_description', $seo['meta_description']) }}</textarea></x-field>
        <x-field label="Meta keywords" name="meta_keywords"><input class="field" name="meta_keywords" value="{{ old('meta_keywords', $seo['meta_keywords']) }}"></x-field>
        <x-field label="Robots" name="robots">
            <select class="field" name="robots">
                @foreach (config('growth.robots') as $value)
                    <option value="{{ $value }}" @selected(old('robots', $seo['robots']) === $value)>{{ $value }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Twitter card" name="twitter_card">
            <select class="field" name="twitter_card">
                @foreach (config('growth.twitter_cards') as $value => $label)
                    <option value="{{ $value }}" @selected(old('twitter_card', $seo['twitter_card']) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="OG title" name="og_title"><input class="field" name="og_title" value="{{ old('og_title', $seo['og_title']) }}"></x-field>
        <x-field label="OG description" name="og_description"><textarea class="field" name="og_description" rows="3">{{ old('og_description', $seo['og_description']) }}</textarea></x-field>
        <x-field label="OG image" name="og_image_id">
            <select class="field" name="og_image_id">
                <option value="">None</option>
                @foreach ($images as $image)
                    <option value="{{ $image->id }}" @selected((int) old('og_image_id', $seo['og_image_id']) === $image->id)>{{ $image->original_name }}</option>
                @endforeach
            </select>
        </x-field>
        <div class="lg:col-span-2"><button class="btn btn-primary" type="submit">Save SEO</button></div>
    </form>
@endsection
