<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use App\Support\SeoSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->hasPermission('seo.view'), 403);
        $tenant = current_tenant();

        return view('tenant.seo.edit', [
            'seo' => SeoSettings::settings($tenant),
            'images' => MediaAsset::query()->where('kind', 'image')->latest()->limit(40)->get(),
        ]);
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('seo.update'), 403);
        $tenant = current_tenant();
        $draft = new Tenant;
        $draft->settings = ['seo' => $request->all()];
        $this->persist($tenant, 'seo', SeoSettings::settings($draft));
        $activity->log('seo.updated', 'Updated SEO settings', $tenant, [], $tenant->id);

        return back()->with('status', 'SEO settings saved.');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function persist(Tenant $tenant, string $group, array $values): void
    {
        $settings = $tenant->settings ?? [];
        $settings[$group] = $values;
        $tenant->settings = $settings;
        $tenant->save();
    }
}
