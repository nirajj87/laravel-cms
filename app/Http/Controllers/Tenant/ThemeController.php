<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\SiteCatalog;
use App\Support\SiteTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function __construct(private readonly SiteCatalog $sites) {}

    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('theme-settings.view'), 403);
        $tenant = current_tenant();

        return view('tenant.theme.edit', [
            'theme' => SiteTheme::theme($tenant->setting('theme', []) ?? []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('theme-settings.update'), 403);
        $this->sites->saveTheme(current_tenant(), $request->all());

        return back()->with('status', 'Theme saved. Colors that are not hex values stay on the previous safe color.');
    }
}
