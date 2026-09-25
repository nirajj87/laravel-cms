<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            return redirect()->route($user->isSuperAdmin() ? 'platform.dashboard' : 'tenant.dashboard');
        }

        return view('home');
    }
}
