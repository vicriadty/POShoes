<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Menyeragamkan response error API (lihat docs/design/api-convention.md).
 *
 * Hanya merender response JSON yang sesuai. Endpoint non-API / browser tetap
 * menggunakan handler bawaan Laravel lewat pengembalian null pada request non-JSON.
 */
final class ApiExceptionHandler
{
    public function handle(Throwable $e, Request $request): ?JsonResponse
    {
        // BIarkan permintaan yang bukan API/JSON ditangani handler bawaan.
        if (! ($request->is('api/*') || $request->expectsJson())) {
            return null;
        }

        return match (true) {
            $e instanceof AuthenticationException => $this->error(
                'Authentication required.', 401, $this->e($e),
            ),
            $e instanceof AuthorizationException, $e instanceof UnauthorizedException => $this->error(
                'Forbidden.', 403, $this->e($e),
            ),
            $e instanceof AccessDeniedHttpException => $this->error(
                $e->getMessage() !== 'This action is unauthorized.' ? $e->getMessage() : 'Forbidden.',
                403, $this->e($e),
            ),
            $e instanceof ValidationException => $this->validation($e),
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => $this->error(
                'Resource not found.', 404, $this->e($e),
            ),
            $e instanceof TooManyRequestsHttpException => $this->error(
                'Too many requests.', 429, $this->e($e),
            ),
            $e instanceof DomainConflictException => $this->error(
                $e->getMessage(), 409, $this->e($e),
            ),
            $e instanceof DomainSystemException => $this->error(
                $e->getMessage(), 422, $this->e($e),
            ),
            default => $this->error(
                'Server error.', 500, $this->e($e),
            ),
        };
    }

    private function validation(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], 422);
    }

    private function error(string $message, int $status, ?string $detail = null): JsonResponse
    {
        $body = ['message' => $message];

        // Di environment non-production, tambahkan detail untuk debugging.
        if (app()->environment('local', 'testing') && $detail !== null) {
            $body['exception'] = $detail;
        }

        return response()->json($body, $status);
    }

    private function e(Throwable $e): string
    {
        return $e->getMessage().' | '.$e::class;
    }
}
