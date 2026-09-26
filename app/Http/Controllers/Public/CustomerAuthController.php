<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function showLogin(Tenant $siteTenant): View
    {
        return view('public.commerce.login', [
            'tenant' => $siteTenant,
            'redirect' => request('redirect'),
        ]);
    }

    public function login(Request $request, Tenant $siteTenant): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $customer = Customer::query()
            ->where('email', strtolower(trim($credentials['email'])))
            ->first();

        if (! $customer || ! Hash::check($credentials['password'], $customer->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        $fallback = route('site.account.dashboard', ['siteTenant' => $siteTenant->slug]);
        $redirect = $credentials['redirect'] ?? null;
        if (is_string($redirect) && str_starts_with($redirect, url('/site/'.$siteTenant->slug))) {
            return redirect()->to($redirect);
        }

        return redirect()->intended($fallback);
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
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^[A-Za-z0-9]+$/'],
            'phone' => ['nullable', 'string', 'max:40'],
        ], [
            'password.regex' => 'Password must be letters and numbers only (no special characters).',
        ]);

        $exists = Customer::query()
            ->where('email', strtolower($data['email']))
            ->exists();
        if ($exists) {
            return back()->withErrors(['email' => 'An account with this email already exists.'])->onlyInput('email');
        }

        $customer = Customer::query()->create([
            'tenant_id' => $siteTenant->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'issued_password' => $data['password'],
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