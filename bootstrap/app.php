<?php

use App\Exceptions\GeneralException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $jsonResponse = fn (string $status, string $message, mixed $data, int $code): JsonResponse =>
            response()->json(compact('status', 'message', 'data'), $code);

        $exceptions->render(function (GeneralException $e): JsonResponse {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'data'    => $e->getData(),
            ], $e->getCode() ?: 400);
        });

        $exceptions->render(function (ValidationException $e) use ($jsonResponse): JsonResponse {
            return $jsonResponse('error', $e->getMessage(), $e->errors(), 422);
        });

        $exceptions->render(function (AuthenticationException $e) use ($jsonResponse): JsonResponse {
            return $jsonResponse('error', 'Unauthenticated.', null, 401);
        });

        $exceptions->render(function (NotFoundHttpException $e) use ($jsonResponse): JsonResponse {
            return $jsonResponse('error', 'Resource not found.', null, 404);
        });

        $exceptions->render(function (HttpException $e) use ($jsonResponse): JsonResponse {
            return $jsonResponse('error', $e->getMessage() ?: 'HTTP error.', null, $e->getStatusCode());
        });

        $exceptions->render(function (Throwable $e) use ($jsonResponse): JsonResponse {
            $code    = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            $message = config('app.debug') ? $e->getMessage() : 'Server error.';

            return $jsonResponse('error', $message, null, $code);
        });
    })->create();
