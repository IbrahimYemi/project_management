<?php

use App\Http\Middleware\CheckIfUserIsActive;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'is_active' => CheckIfUserIsActive::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return ApiResponseTrait::sendError('Route not found', [], 404);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return ApiResponseTrait::sendError('Resource not found', [], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return ApiResponseTrait::sendError('Method not allowed', [], 405);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return ApiResponseTrait::sendError('Unauthenticated', [], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            Log::info('AuthorizationException caught in handler: ' . $e->getMessage());
            return ApiResponseTrait::sendError('Unauthorized action', [], 403);
        });

        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            return ApiResponseTrait::sendError('Unauthorized action', [], 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return ApiResponseTrait::sendError($e->getMessage(), [], 403);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            return ApiResponseTrait::sendError('Validation Error', $e->errors(), 422);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return ApiResponseTrait::sendError('Too many requests. Slow down!', [], 429);
        });

        $exceptions->render(function (HttpResponseException $e, Request $request) {
            return $e->getResponse();
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            Log::error('Database Query Error: ' . $e->getMessage());
            return ApiResponseTrait::sendError('Database error occurred. Please try again later.', [], 500);
        });

        $exceptions->render(function (BindingResolutionException $e, Request $request) {
            Log::error('Service Binding Resolution Error: ' . $e->getMessage());
            return ApiResponseTrait::sendError('Internal Server Error. Please contact support.', [], 500);
        });

        $exceptions->render(function (ErrorException $e, Request $request) {
            Log::error('Major Error: ' . $e->getMessage());
            return ApiResponseTrait::sendError('Internal Server Error. Please contact support.', [], 500);
        });

        // Catch-all (Final fallback)
        $exceptions->render(function (Throwable $e, Request $request) {
            // Log::error('Exception type: ' . get_class($e));

            Log::error('Server Error: ' . $e->getMessage());
            return ApiResponseTrait::sendError('Seems we have broken something, Try again later!', [], 500);
        });
    })
    ->create();
