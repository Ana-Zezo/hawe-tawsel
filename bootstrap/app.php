<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureDriver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['api'])
                ->prefix('api/')
                ->name('api.')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['api'])
                ->prefix('api/')
                ->name('api.')
                ->group(base_path('routes/driver.php'));
        },
    )->withMiddleware(function (Middleware $middleware) {
        //
    })->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $exception, $request) {
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'status' => false,
                    'message' => $exception->getMessage(),
                    // 'errors' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                return response()->json([
                    'status' => false,
                    'message' => 'الصفحة غير موجود',
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 500);
        });
    })->create();
    
    