<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use App\Support\AnalyticsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('analytics.view'), 403);

        return view('tenant.analytics.edit', [
            'analytics' => AnalyticsSettings::settings(current_tenant()),
        ]);
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('analytics.update'), 403);
        $tenant = current_tenant();
        $draft = new Tenant;
        $draft->settings = ['analytics' => $request->all()];
        $settings = $tenant->settings ?? [];
        $settings['analytics'] = AnalyticsSettings::settings($draft);
        $tenant->settings = $settings;
        $tenant->save();
        $activity->log('analytics.updated', 'Updated analytics settings', $tenant, [
            'measurement_id' => $settings['analytics']['measurement_id'],
        ], $tenant->id);

        return back()->with('status', 'Analytics settings saved.');
    }
}
