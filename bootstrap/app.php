<?php

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantAccess;
use App\Http\Middleware\ShareTenantBrand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            return route($user?->isSuperAdmin() ? 'platform.dashboard' : 'tenant.dashboard');
        });

        $middleware->web(append: [
            ShareTenantBrand::class,
        ]);

        $middleware->alias([
            'superadmin' => EnsureSuperAdmin::class,
            'tenant' => EnsureTenantAccess::class,
            'module' => EnsureModuleEnabled::class,
            'permission' => EnsurePermission::class,
        ]);

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EnsureTenantAccess::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
