<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetClinic::class,
            \App\Http\Middleware\EnsureClinicBillingIsActive::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetClinicForApi::class,
        ]);

        $middleware->alias([
            'platform' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'tenant' => \App\Http\Middleware\EnsureTenantUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $status = $e->getStatusCode();
            $view = 'errors.' . $status;

            if (view()->exists($view)) {
                return response()->view($view, [
                    'status' => $status,
                    'message' => $e->getMessage() ?: null,
                ], $status);
            }

            return null;
        });
    })->create();
