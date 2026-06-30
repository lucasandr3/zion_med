<?php

use App\Support\ApiErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/asaas',
        ]);

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
            'tenant.billing' => \App\Http\Middleware\EnsureTenantBillingActive::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerifiedApi::class,
            'business_hub.connector' => \App\Http\Middleware\AuthenticateBusinessHubConnector::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            app(\App\Services\ErrorHubService::class)->reportException($e);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ApiErrorResponse::validation($e->errors(), $e->getMessage() ?: null);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ApiErrorResponse::fromStatus(401);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ApiErrorResponse::fromStatus(404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: ApiErrorResponse::messageForStatus($status);

                return ApiErrorResponse::fromStatus($status, $message);
            }

            $status = $e->getStatusCode();
            $view = 'errors.'.$status;

            if (view()->exists($view)) {
                return response()->view($view, [
                    'status' => $status,
                    'message' => $e->getMessage() ?: null,
                ], $status);
            }

            return null;
        });
    })->create();
