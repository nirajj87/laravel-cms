@extends('layouts.app')

@php
    $hub = $hub ?? 'tenant.posts.types';
    $isBuilder = $hub === 'tenant.form-builder';
@endphp

@section('title', $isBuilder ? 'Add form' : 'Add content type')
@section('kicker', $isBuilder ? 'Form Builder' : 'Content')
@section('heading', $isBuilder ? 'Add form' : 'Add content type')

@section('content')
    <form method="POST" action="{{ route($hub.'.store') }}" class="card max-w-xl p-5">
        @csrf
        <x-field label="Name" name="name">
            <input class="field" name="name" value="{{ old('name') }}" required>
        </x-field>
        <x-field label="Start from" name="preset">
            <select class="field" name="preset">
                @foreach ($presets as $key => $preset)
                    <option value="{{ $key }}" @selected(old('preset', 'custom') === $key)>{{ $preset['name'] }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Description" name="description">
            <textarea class="field" name="description" rows="3">{{ old('description') }}</textarea>
        </x-field>
        <div class="flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">{{ $isBuilder ? 'Create form' : 'Create content type' }}</button>
            <a class="btn btn-secondary" href="{{ route($hub.'.index') }}">Cancel</a>
        </div>
    </form>
@endsection
