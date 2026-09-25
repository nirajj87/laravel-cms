@extends('layouts.guest')

@section('content')
    <div class="card max-w-lg p-8 text-center">
        <p class="text-sm font-semibold text-teal-800">403</p>
        <h1 class="mt-2 text-2xl font-semibold">You do not have access</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $exception->getMessage() ?: 'This action is not allowed for your account.' }}</p>
        <a class="btn btn-primary mt-6" href="{{ route('home') }}">Go back</a>
    </div>
@endsection
