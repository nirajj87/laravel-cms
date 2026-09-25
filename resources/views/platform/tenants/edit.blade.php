@extends('layouts.app')

@section('title', 'Edit '.$tenant->name)
@section('kicker', 'Platform')
@section('heading', 'Edit '.$tenant->name)

@section('content')
    <form method="POST" action="{{ route('platform.tenants.update', $tenant) }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-2">
        @csrf
        @method('PUT')
        <section class="card p-5">
            <h2 class="mb-4 font-semibold">Workspace</h2>
            @include('platform.tenants._fields')
            <label class="mb-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_favicon" value="1"> Remove favicon</label>
        </section>
        <section class="card p-5">
            <h2 class="mb-1 font-semibold">Modules</h2>
            <p class="mb-4 text-sm text-slate-500">Turning a module off hides it and blocks its routes. Owner access follows the modules that remain.</p>
            @include('partials.module-picker')
            <button class="btn btn-primary mt-6" type="submit">Save changes</button>
        </section>
    </form>
@endsection
