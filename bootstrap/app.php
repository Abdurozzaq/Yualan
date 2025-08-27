<?php

use App\Http\Middleware\CheckSuperadmin;
use App\Http\Middleware\CheckTenantAccess;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'tenant.access' => CheckTenantAccess::class,
            'superadmin.access' => CheckSuperadmin::class,
            'admin.access' => CheckAdmin::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'sales/ipaymu/notify',
            'subscription/notify',
            '*/products/import',
            '*/products/import/error-rows',
            'midtrans/callback',
            '*/sales/store',
            '*/voucher/*/use',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
