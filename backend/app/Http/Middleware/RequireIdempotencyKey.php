<?php

namespace App\Http\Middleware;

use App\Shared\Exceptions\DomainException;
use App\Shared\Idempotency\IdempotencyService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces `Idempotency-Key` on retriable HTTP posting endpoints
 * (agent_docs/08_API_SPECIFICATION.md §1).
 */
final class RequireIdempotencyKey
{
    public function __construct(private readonly IdempotencyService $idempotency) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header(IdempotencyService::HEADER));
        if ($key === '' || strlen($key) > 128) {
            throw new DomainException(
                'Header Idempotency-Key wajib disertakan untuk endpoint posting.',
                'IDEMPOTENCY_KEY_REQUIRED',
                422,
                [IdempotencyService::HEADER => ['Header Idempotency-Key wajib diisi (maks. 128 karakter).']],
            );
        }

        $operation = $request->route()?->getName() ?? ($request->method().':'.$request->path());
        $hash = IdempotencyService::hashRequest($request);

        $result = $this->idempotency->execute(
            $key,
            $operation,
            $hash,
            $request->user()?->getKey(),
            function () use ($next, $request): array {
                /** @var Response $response */
                $response = $next($request);
                $decoded = json_decode((string) $response->getContent(), true);

                return [
                    'status' => $response->getStatusCode(),
                    'body' => is_array($decoded) ? $decoded : ['data' => $decoded],
                ];
            },
        );

        return new JsonResponse(
            $result['body'],
            $result['status'],
            ['Idempotent-Replayed' => $result['replayed'] ? 'true' : 'false'],
        );
    }
}
