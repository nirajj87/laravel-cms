<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\ModuleRegistry;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(ModuleRegistry $modules): View
    {
        abort_unless(auth()->user()?->hasPermission('permissions.view'), 403);

        $tenant = current_tenant();
        $permissions = $modules->permissionsForEnabledModules($tenant);

        return view('tenant.permissions.index', [
            'grouped' => $permissions->groupBy('module'),
            'modules' => Module::query()->whereIn('slug', $permissions->pluck('module')->unique())->get()->keyBy('slug'),
        ]);
    }
}
