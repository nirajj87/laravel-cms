<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            $tenantId = $request->session()->get('platform_tenant_id');
            $tenant = $tenantId ? Tenant::query()->find($tenantId) : null;

            if (! $tenant) {
                return redirect()
                    ->route('platform.dashboard')
                    ->with('status', 'Open a workspace from the tenants list.');
            }

            $this->context->set($tenant);

            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant || ! $user->tenant_id) {
            abort(403, 'This account is not attached to a workspace.');
        }

        if ($tenant->status !== TenantStatus::Active || $user->status->value !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This workspace or account is inactive.']);
        }

        $hostTenant = $this->resolver->fromHost($request);

        if ($hostTenant && $hostTenant->id !== $tenant->id) {
            abort(403, 'This account does not belong to this site.');
        }

        $this->context->set($tenant);

        return $next($request);
    }
}
