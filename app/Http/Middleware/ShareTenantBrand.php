<?php

namespace App\Http\Middleware;

use App\Support\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareTenantBrand
{
    public function __construct(private readonly TenantResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            view()->share('hostTenant', $this->resolver->fromHost($request));
        } catch (\Throwable) {
            view()->share('hostTenant', null);
        }

        return $next($request);
    }
}
