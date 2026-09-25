<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdatePlatformSettingsRequest;
use App\Services\ActivityLogger;
use App\Services\PlatformSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
        private readonly ActivityLogger $activity,
    ) {}

    public function edit(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $definitions = $this->settings->definitions();
        $values = [];

        foreach (array_keys($definitions) as $group) {
            $values[$group] = $this->settings->group($group);
            unset($values[$group]['password']);
        }

        return view('platform.settings.edit', [
            'definitions' => $definitions,
            'values' => $values,
        ]);
    }

    public function update(UpdatePlatformSettingsRequest $request, string $group): RedirectResponse
    {
        $definitions = $this->settings->definitions()[$group] ?? abort(404);
        $this->settings->updateGroup($group, $request->validated(), $definitions);
        $this->activity->log('settings.updated', 'Updated platform '.$group.' settings');

        return redirect()
            ->route('platform.settings.edit', ['group' => $group])
            ->with('status', ucfirst($group).' settings saved.');
    }
}
