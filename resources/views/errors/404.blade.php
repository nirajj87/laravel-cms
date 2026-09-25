@extends('layouts.guest')

@section('content')
    <div class="card max-w-lg p-8 text-center">
        <p class="text-sm font-semibold text-teal-800">404</p>
        <h1 class="mt-2 text-2xl font-semibold">That page is not here</h1>
        <a class="btn btn-primary mt-6" href="{{ route('home') }}">Go back</a>
    </div>
@endsection
