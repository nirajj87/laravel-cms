<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulePlaceholderController extends Controller
{
    public function show(Request $request, ModuleRegistry $modules): View
    {
        $slug = (string) $request->route('module');
        $definition = $modules->definition($slug);
        $tenant = current_tenant() ?? $request->user()?->tenant;

        abort_unless($definition && $tenant, 404);
        abort_unless($tenant->hasModule($slug), 403);
        abort_unless($request->user()?->hasPermission($slug.'.view'), 403);

        return view('tenant.modules.placeholder', [
            'slug' => $slug,
            'module' => $definition,
        ]);
    }
}
