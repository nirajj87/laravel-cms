<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Services\DashboardStats;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardStats $stats): View
    {
        $summary = $stats->platform();

        return view('platform.dashboard', [
            'summary' => $summary,
            'recentTenants' => Tenant::query()->latest()->limit(6)->get(['id', 'name', 'slug', 'status', 'created_at']),
            'activity' => ActivityLog::query()->with(['user:id,name', 'tenant:id,name'])->latest()->limit(8)->get(),
        ]);
    }
}
