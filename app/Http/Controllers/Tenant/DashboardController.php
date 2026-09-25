<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Module;
use App\Services\DashboardStats;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardStats $stats): View
    {
        $tenant = current_tenant();
        abort_unless($tenant, 403);

        $enabled = $tenant->enabledModuleSlugs();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'stats' => $stats->tenant($tenant),
            'modules' => Module::query()->whereIn('slug', $enabled)->orderBy('sort_order')->get(),
            'activity' => ActivityLog::query()->where('tenant_id', $tenant->id)->with('user:id,name')->latest()->limit(8)->get(),
        ]);
    }
}
