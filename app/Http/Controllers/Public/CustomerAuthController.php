<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function showLogin(Tenant $siteTenant): View
    {
        return view('public.commerce.login', ['tenant' => $siteTenant]);
    }

    public function login(Request $request, Tenant $siteTenant): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('customer')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'tenant_id' => $siteTenant->id,
        ], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('site.account.dashboard', ['siteTenant' => $siteTenant->slug]));
    }

    public function showRegister(Tenant $siteTenant): View
    {
        return view('public.commerce.register', ['tenant' => $siteTenant]);
    }

    public function register(Request $request, Tenant $siteTenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $exists = Customer::query()
            ->where('email', $data['email'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['email' => 'An account with this email already exists.'])->onlyInput('email');
        }

        $customer = Customer::query()->create([
            'tenant_id' => $siteTenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('site.account.dashboard', ['siteTenant' => $siteTenant->slug])
            ->with('status', 'Welcome! Your account is ready.');
    }

    public function logout(Request $request, Tenant $siteTenant): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('site.home', ['siteTenant' => $siteTenant->slug]);
    }
}
