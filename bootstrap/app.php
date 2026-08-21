<?php

use App\Http\Middleware\EnsureActiveDevice;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsPropertyOwner;
use App\Http\Middleware\EnsureUserIsProvider;
use App\Http\Middleware\EnsureUserIsResident;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'provider' => EnsureUserIsProvider::class,
            'admin' => EnsureUserIsAdmin::class,
            'property_owner' => EnsureUserIsPropertyOwner::class,
            'resident' => EnsureUserIsResident::class,
            'active_device' => EnsureActiveDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'status' => false,
                        'message' => 'بيانات المدخلات غير صالحة.',
                        'errors' => $e->errors(),
                    ], 422);
                }
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'status' => false,
                        'message' => 'غير مصرح بالدخول، يرجى تسجيل الدخول أولاً.',
                    ], 401);
                }
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'status' => false,
                        'message' => 'العنصر المطلوب غير موجود.',
                    ], 404);
                }

                return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
            }
        });
    })->create();
