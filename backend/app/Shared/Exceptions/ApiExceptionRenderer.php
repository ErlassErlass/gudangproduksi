<?php

namespace App\Shared\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders every API error using the consistent envelope:
 * `{ "message": string, "code": string, "errors"?: object }`.
 */
final class ApiExceptionRenderer
{
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => $this->respond(422, 'VALIDATION_ERROR', $e->getMessage(), $e->errors()),
            $e instanceof DomainException => $this->respond($e->status(), $e->errorCode(), $e->getMessage(), $e->errors()),
            $e instanceof AuthenticationException => $this->respond(401, 'UNAUTHENTICATED', 'Sesi tidak valid atau telah berakhir.'),
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => $this->respond(403, 'FORBIDDEN', 'Anda tidak memiliki izin untuk tindakan ini.'),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => $this->respond(404, 'NOT_FOUND', 'Data tidak ditemukan.'),
            $e instanceof ThrottleRequestsException => $this->respond(429, 'TOO_MANY_REQUESTS', 'Terlalu banyak permintaan. Coba lagi nanti.', [], $e->getHeaders()),
            $e instanceof HttpExceptionInterface => $this->respond($e->getStatusCode(), 'HTTP_ERROR', $e->getMessage() ?: 'Permintaan tidak dapat diproses.', [], $e->getHeaders()),
            default => $this->respond(
                500,
                'SERVER_ERROR',
                config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, string>  $headers
     */
    private function respond(int $status, string $code, string $message, array $errors = [], array $headers = []): JsonResponse
    {
        $body = ['message' => $message, 'code' => $code];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status, $headers);
    }
}
