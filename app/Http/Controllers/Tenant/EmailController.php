<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\ActivityLogger;
use App\Support\EmailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('email.view'), 403);

        return view('tenant.email.edit', [
            'email' => EmailSettings::settings(current_tenant()),
            'templates' => EmailTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('email.update'), 403);
        $tenant = current_tenant();
        $settings = $tenant->settings ?? [];
        $settings['email'] = EmailSettings::store($request->all(), $settings['email'] ?? []);
        $tenant->settings = $settings;
        $tenant->save();

        foreach ((array) $request->input('templates', []) as $id => $input) {
            $template = EmailTemplate::query()->whereKey($id)->first();

            if (! $template || ! is_array($input)) {
                continue;
            }

            $template->fill([
                'subject' => mb_substr(trim(strip_tags((string) ($input['subject'] ?? $template->subject))), 0, 180),
                'body' => mb_substr(trim(strip_tags((string) ($input['body'] ?? $template->body))), 0, 5000),
                'enabled' => $request->boolean('templates.'.$id.'.enabled'),
            ])->save();
        }

        $activity->log('email.updated', 'Updated email settings', $tenant, [
            'host' => $settings['email']['host'] ?? null,
        ], $tenant->id);

        return back()->with('status', 'Email settings saved.');
    }
}
