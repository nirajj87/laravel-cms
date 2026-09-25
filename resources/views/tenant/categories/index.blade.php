@extends('layouts.app')

@section('title', 'Categories')
@section('kicker', 'Content')
@section('heading', 'Categories')

@section('content')
    <div class="mb-4 flex justify-end">
        @permission('categories.create')
            <a class="btn btn-primary" href="{{ route('tenant.categories.create') }}">Add category</a>
        @endpermission
    </div>
    <section class="card px-5">
        @forelse ($categories as $node)
            @include('tenant.categories._branch', ['nodes' => [$node], 'depth' => 0])
        @empty
            <p class="py-8 text-sm text-slate-500">No categories yet.</p>
        @endforelse
    </section>
@endsection
