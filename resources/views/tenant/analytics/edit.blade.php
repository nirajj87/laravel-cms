@extends('layouts.app')

@section('title', 'Analytics')
@section('kicker', 'Growth')
@section('heading', 'Analytics')

@section('content')
    <form method="POST" action="{{ route('tenant.analytics.update') }}" class="card grid max-w-2xl gap-4 p-5">
        @csrf
        @method('PUT')
        <p class="text-sm text-slate-600">Measurement IDs are checked against a pattern. Invalid values are ignored and never written into the page.</p>
        <input type="hidden" name="enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $analytics['enabled']))> Enable Google Analytics</label>
        <x-field label="Measurement ID" name="measurement_id"><input class="field" name="measurement_id" value="{{ old('measurement_id', $analytics['measurement_id']) }}" placeholder="G-XXXXXXXXXX"></x-field>
        <input type="hidden" name="gtm_enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="gtm_enabled" value="1" @checked(old('gtm_enabled', $analytics['gtm_enabled']))> Enable Google Tag Manager</label>
        <x-field label="Tag Manager ID" name="gtm_id"><input class="field" name="gtm_id" value="{{ old('gtm_id', $analytics['gtm_id']) }}" placeholder="GTM-XXXX"></x-field>
        <input type="hidden" name="pixel_enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="pixel_enabled" value="1" @checked(old('pixel_enabled', $analytics['pixel_enabled']))> Enable Meta Pixel</label>
        <x-field label="Meta Pixel ID" name="meta_pixel_id"><input class="field" name="meta_pixel_id" value="{{ old('meta_pixel_id', $analytics['meta_pixel_id']) }}" placeholder="Numeric ID"></x-field>
        <button class="btn btn-primary" type="submit">Save analytics</button>
    </form>
@endsection
