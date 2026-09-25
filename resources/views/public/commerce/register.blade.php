@extends('public.layout')

@section('content')
<section class="site-browse" style="margin-top:1.5rem;">
    <div>
        <p class="site-kicker">Account</p>
        <h2>Create account</h2>
    </div>
</section>
<form method="POST" action="{{ route('site.account.register.store', ['siteTenant' => $tenant->slug]) }}" style="display:grid;gap:1rem;max-width:28rem;">
    @csrf
    <label>Name<input class="site-input" name="name" value="{{ old('name') }}" required></label>
    <label>Email<input class="site-input" type="email" name="email" value="{{ old('email') }}" required></label>
    @error('email')<p style="color:#b91c1c;margin:0;">{{ $message }}</p>@enderror
    <label>Phone<input class="site-input" name="phone" value="{{ old('phone') }}"></label>
    <label>Password<input class="site-input" type="password" name="password" required></label>
    <label>Confirm password<input class="site-input" type="password" name="password_confirmation" required></label>
    <button class="site-btn" type="submit">Register</button>
    <p style="margin:0;">Already have an account? <a href="{{ route('site.account.login', ['siteTenant' => $tenant->slug]) }}">Sign in</a></p>
</form>
@endsection
