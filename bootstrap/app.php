<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
            'active' => \App\Http\Middleware\ActiveAccount::class,
        ]);
        $middleware->prepend(\App\Http\Middleware\RequestId::class);

        $middleware->api([
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            HandleCors::class, // thêm CORS đây là đúng
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Unhandled application exception', [
                'type' => $exception::class, 'request_id' => app()->bound('request') ? request()->attributes->get('request_id') : null,
            ]);

            return false;
        });
        $exceptions->render(function (\Throwable $exception, \Illuminate\Http\Request $request) {
            if ($exception instanceof \App\Modules\Pricing\Domain\InvalidQuote) {
                return response()->json(['message' => $exception->getMessage(), 'errors' => ['items' => [$exception->getMessage()]]], 422);
            }
            if ($exception instanceof \App\Exceptions\InventoryConflict) {
                $status = match ($exception->reasonCode) {
                    'forbidden_actor' => 403, 'inventory_unavailable' => 503, 'insufficient_stock' => 400, default => 409,
                };

                return response()->json(['message' => $exception->getMessage(), 'code' => $exception->reasonCode,
                    'request_id' => $request->attributes->get('request_id')], $status);
            }
            if ($exception instanceof \Illuminate\Validation\ValidationException
                || $exception instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }
            $status = $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $exception->getStatusCode() : 500;

            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return response('Không thể xử lý yêu cầu. Mã tra cứu: '.$request->attributes->get('request_id'), $status);
            }

            return response()->json(['message' => $status < 500 ? ($exception->getMessage() ?: 'Yêu cầu không hợp lệ.') : 'Không thể xử lý yêu cầu.', 'request_id' => $request->attributes->get('request_id')], $status);
        });
    })->create();
