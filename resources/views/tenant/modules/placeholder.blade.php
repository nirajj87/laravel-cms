@extends('layouts.app')

@section('title', $module['name'])
@section('kicker', 'Module')
@section('heading', $module['name'])

@section('content')
    <section class="card max-w-2xl p-6">
        <p class="text-sm font-medium uppercase tracking-wide text-teal-800">Enabled</p>
        <p class="mt-3 text-slate-700">{{ $module['description'] }}</p>
        <p class="mt-3 text-sm text-slate-500">
            This module is installed and protected by <code class="rounded bg-stone-100 px-1">{{ $slug }}.view</code>.
            Content fields, layouts, and publishing tools are the next layer. They will read configuration instead of a fixed content type.
        </p>
    </section>
@endsection
