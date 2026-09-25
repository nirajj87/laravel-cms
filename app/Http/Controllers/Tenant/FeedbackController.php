<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\FeedbackStatus;
use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Feedback::class);

        return view('tenant.feedback.index', [
            'feedback' => Feedback::query()->with('post:id,title')->latest()->paginate(20),
            'statuses' => FeedbackStatus::cases(),
            'captcha' => $this->captcha(current_tenant()),
        ]);
    }

    public function update(Request $request, Feedback $feedback, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('update', $feedback);
        $data = $request->validate([
            'status' => ['required', Rule::enum(FeedbackStatus::class)],
        ]);
        $before = $feedback->status->value;
        $feedback->update(['status' => $data['status']]);
        $activity->log('feedback.updated', 'Updated feedback from '.$feedback->name, $feedback, [
            'old' => ['status' => $before],
            'new' => ['status' => $feedback->status->value],
        ], $feedback->tenant_id);

        return back()->with('status', 'Feedback updated.');
    }

    public function destroy(Feedback $feedback, ActivityLogger $activity): RedirectResponse
    {
        $this->authorize('delete', $feedback);
        $activity->log('feedback.deleted', 'Deleted feedback from '.$feedback->name, $feedback, [], $feedback->tenant_id);
        $feedback->delete();

        return back()->with('status', 'Feedback deleted.');
    }

    public function settings(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('feedback.update'), 403);
        $tenant = current_tenant();
        $secret = trim((string) $request->input('captcha_secret'));
        $current = $this->captcha($tenant);
        $provider = (string) $request->input('captcha_provider');
        $settings = $tenant->settings ?? [];
        $settings['feedback'] = [
            'captcha_enabled' => $request->boolean('captcha_enabled'),
            'captcha_provider' => array_key_exists($provider, config('growth.captcha_providers')) ? $provider : 'none',
            'captcha_site_key' => mb_substr(trim(strip_tags((string) $request->input('captcha_site_key'))), 0, 180),
            'captcha_secret' => $secret !== '' ? 'enc:'.Crypt::encryptString($secret) : ($current['secret_set'] ? $tenant->setting('feedback.captcha_secret') : null),
        ];
        $tenant->settings = $settings;
        $tenant->save();
        $activity->log('feedback.settings', 'Updated feedback protection', $tenant, [
            'captcha_enabled' => $settings['feedback']['captcha_enabled'],
            'captcha_provider' => $settings['feedback']['captcha_provider'],
        ], $tenant->id);

        return back()->with('status', 'Feedback protection saved.');
    }

    /**
     * @return array{enabled: bool, provider: string, site_key: string, secret_set: bool}
     */
    private function captcha(Tenant $tenant): array
    {
        $input = $tenant->setting('feedback', []) ?? [];
        $provider = (string) ($input['captcha_provider'] ?? 'none');

        return [
            'enabled' => filter_var($input['captcha_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'provider' => array_key_exists($provider, config('growth.captcha_providers')) ? $provider : 'none',
            'site_key' => mb_substr(trim(strip_tags((string) ($input['captcha_site_key'] ?? ''))), 0, 180),
            'secret_set' => is_string($input['captcha_secret'] ?? null) && str_starts_with($input['captcha_secret'], 'enc:'),
        ];
    }
}
