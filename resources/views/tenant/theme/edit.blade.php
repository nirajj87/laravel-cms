@extends('layouts.app')

@section('title', 'Theme')
@section('kicker', 'Design')
@section('heading', 'Theme')

@section('content')
    <form method="POST" action="{{ route('tenant.theme.update') }}" class="card grid gap-4 p-5 lg:grid-cols-2">
        @csrf
        @method('PUT')
        @foreach (['primary' => 'Primary', 'secondary' => 'Secondary', 'background' => 'Background', 'text' => 'Text', 'button' => 'Button'] as $key => $label)
            <x-field :label="$label.' color'" :name="$key">
                <input class="field" type="color" name="{{ $key }}" value="{{ $theme[$key] }}">
            </x-field>
        @endforeach
        <x-field label="Font" name="font">
            <select class="field" name="font">
                @foreach (config('site.fonts') as $value => $label)
                    <option value="{{ $value }}" @selected($theme['font'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Border radius" name="radius">
            <select class="field" name="radius">
                @foreach (config('site.radii') as $value)
                    <option value="{{ $value }}" @selected((string) $theme['radius'] === (string) $value)>{{ $value }} px</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Container width" name="container">
            <select class="field" name="container">
                @foreach (config('site.containers') as $value)
                    <option value="{{ $value }}" @selected((string) $theme['container'] === (string) $value)>{{ $value === 'full' ? 'Full width' : $value.' px' }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Grid columns" name="columns">
            <select class="field" name="columns">
                @foreach (config('site.columns') as $value)
                    <option value="{{ $value }}" @selected((int) $theme['columns'] === (int) $value)>{{ $value }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Card style" name="card_style">
            <select class="field" name="card_style">
                @foreach (config('site.card_styles') as $value => $label)
                    <option value="{{ $value }}" @selected($theme['card_style'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Header style" name="header_style">
            <select class="field" name="header_style">
                @foreach (config('site.header_styles') as $value => $label)
                    <option value="{{ $value }}" @selected($theme['header_style'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Footer style" name="footer_style">
            <select class="field" name="footer_style">
                <option value="solid" @selected($theme['footer_style'] === 'solid')>Solid</option>
                <option value="muted" @selected($theme['footer_style'] === 'muted')>Muted</option>
            </select>
        </x-field>
        <x-field label="Mode" name="mode">
            <select class="field" name="mode">
                @foreach (config('site.modes') as $value => $label)
                    <option value="{{ $value }}" @selected($theme['mode'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Listing" name="listing">
            <select class="field" name="listing">
                @foreach (config('site.listing_modes') as $value => $label)
                    <option value="{{ $value }}" @selected($theme['listing'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Cards per page" name="per_page">
            <input class="field" type="number" min="4" max="48" name="per_page" value="{{ $theme['per_page'] }}">
        </x-field>
        <fieldset class="lg:col-span-2">
            <legend class="label">Motion</legend>
            <div class="flex flex-wrap gap-4 text-sm">
                @foreach (['hover' => 'Card hover', 'card_fade' => 'Fade', 'smooth_scroll' => 'Smooth scroll', 'button_press' => 'Button', 'image_hover' => 'Image hover', 'page_fade' => 'Page fade'] as $key => $label)
                    <label>
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($theme[$key])>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-500">Effects turn off when the visitor asks for reduced motion.</p>
        </fieldset>
        @permission('theme-settings.update')
            <button class="btn btn-primary" type="submit">Save theme</button>
        @endpermission
    </form>
@endsection
