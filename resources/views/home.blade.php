@extends('layouts.guest')

@section('content')
    <div class="w-full max-w-3xl">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-teal-800">Multi-tenant content platform</p>
        <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">One workspace for every kind of content.</h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-600">
            Tenants publish tools, services, articles, products, and custom types from modules you turn on.
            Fields, layouts, and permissions stay configurable instead of hard-coded.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
        </div>
        <dl class="mt-12 grid gap-4 sm:grid-cols-3">
            <div class="card p-4">
                <dt class="text-sm font-semibold">Isolated tenants</dt>
                <dd class="mt-1 text-sm text-slate-600">Each workspace only sees its own people, roles, and modules.</dd>
            </div>
            <div class="card p-4">
                <dt class="text-sm font-semibold">Assignable modules</dt>
                <dd class="mt-1 text-sm text-slate-600">Disabled modules disappear from the menu and reject their routes.</dd>
            </div>
            <div class="card p-4">
                <dt class="text-sm font-semibold">Real permissions</dt>
                <dd class="mt-1 text-sm text-slate-600">Checkboxes map to backend policies, not just hidden links.</dd>
            </div>
        </dl>
    </div>
@endsection
