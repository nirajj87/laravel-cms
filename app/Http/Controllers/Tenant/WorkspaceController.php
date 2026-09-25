<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function exit(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $request->session()->forget('platform_tenant_id');

        return redirect()->route('platform.dashboard')->with('status', 'Returned to the platform.');
    }
}
