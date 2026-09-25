<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateSettingsRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('settings.view'), 403);

        return view('tenant.settings.edit', [
            'tenant' => current_tenant(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $tenant = current_tenant();
        $settings = $tenant->settings ?? [];
        $settings['timezone'] = $request->validated('timezone');
        $settings['tagline'] = $request->validated('tagline');
        $settings['primary_color'] = $request->validated('primary_color');

        $tenant->fill($request->safe()->only(['email', 'phone', 'address']));
        $tenant->settings = $settings;
        $tenant->save();

        $activity->log('tenant.settings', 'Updated workspace settings', $tenant, [], $tenant->id);

        return back()->with('status', 'Workspace settings saved.');
    }
}
