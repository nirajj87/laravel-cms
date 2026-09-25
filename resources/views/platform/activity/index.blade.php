@extends('layouts.app')

@section('title', 'Activity')
@section('kicker', 'Platform')
@section('heading', 'Activity')

@section('content')
    <form method="GET" class="mb-4 flex gap-2">
        <select class="field sm:w-72" name="action">
            <option value="">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>
    <section class="card p-5">
        @include('partials.activity-list', ['activity' => $activity])
    </section>
    <div class="mt-4">{{ $activity->links() }}</div>
@endsection
