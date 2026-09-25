<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = $this->context->get() ?? $request->user()?->tenant;

        if (! $tenant || ! $tenant->hasModule($module)) {
            abort(403, 'This module is not enabled for the workspace.');
        }

        return $next($request);
    }
}
