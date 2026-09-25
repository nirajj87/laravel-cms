@extends('public.layout')

@section('content')
    <article style="max-width:46rem;margin:2rem auto;">
        <p style="opacity:.7;"><a href="{{ route('site.home', ['siteTenant' => $tenant->slug]) }}">{{ $tenant->name }}</a></p>
        <h1 style="font-size:2.4rem;margin:.4rem 0 1rem;">{{ $page->title }}</h1>
        <div>{!! \App\Support\SafeHtml::clean($page->body) !!}</div>
    </article>
@endsection
