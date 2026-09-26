@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Account</p>
        <h2>Sign in</h2>
    </div>
</section>
@if (session('status'))<p>{{ session('status') }}</p>@endif
@if ($errors->any())
    <p style="color:#b91c1c;margin-bottom:1rem;">{{ $errors->first() }}</p>
@endif
<form method="POST" action="{{ route('site.account.login.store', ['siteTenant' => $tenant->slug]) }}" style="display:grid;gap:1rem;max-width:28rem;">
    @csrf
    @if (!empty($redirect))
        <input type="hidden" name="redirect" value="{{ $redirect }}">
    @endif
    <label>Email<input class="site-input" type="email" name="email" value="{{ old('email') }}" required autofocus></label>
    <label>Password<input class="site-input" type="password" name="password" required></label>
    <label style="display:flex;gap:.5rem;align-items:center;"><input type="checkbox" name="remember" value="1"> Remember me</label>
    <button class="site-btn" type="submit">Sign in</button>
    <p style="margin:0;">New here? <a href="{{ route('site.account.register', ['siteTenant' => $tenant->slug]) }}">Create an account</a></p>
</form>
@endsection