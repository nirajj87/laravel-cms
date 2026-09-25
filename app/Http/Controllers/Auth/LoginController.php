<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use App\Support\TenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, TenantResolver $resolver, ActivityLogger $activity): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $activity->log('auth.failed', 'Failed sign-in', null, [
                'email' => strtolower((string) $request->input('email')),
            ]);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = $request->user();

        if ($user->status !== UserStatus::Active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account is inactive.',
            ]);
        }

        if ($user->tenant && $user->tenant->status !== TenantStatus::Active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This workspace is inactive. Contact the platform admin.',
            ]);
        }

        $hostTenant = $resolver->fromHost($request);

        if ($hostTenant && ! $user->isSuperAdmin() && (int) $user->tenant_id !== (int) $hostTenant->id) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account does not belong to this site.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->isSuperAdmin()) {
            $request->session()->forget('platform_tenant_id');
        }

        $activity->log('auth.login', 'Signed in', $user);

        $home = $user->isSuperAdmin() ? 'platform.dashboard' : 'tenant.dashboard';

        return redirect()->intended(route($home));
    }

    public function destroy(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $activity->log('auth.logout', 'Signed out', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
