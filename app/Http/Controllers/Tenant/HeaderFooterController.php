<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\SiteCatalog;
use App\Support\SiteTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HeaderFooterController extends Controller
{
    public function __construct(private readonly SiteCatalog $sites) {}

    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('header-footer.view'), 403);

        return view('tenant.header.edit', [
            'site' => SiteTheme::site(current_tenant()->setting('site', []) ?? []),
            'platforms' => config('site.social_platforms'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('header-footer.update'), 403);
        $this->sites->saveSite(current_tenant(), $request->all());

        return back()->with('status', 'Header and footer saved.');
    }
}
