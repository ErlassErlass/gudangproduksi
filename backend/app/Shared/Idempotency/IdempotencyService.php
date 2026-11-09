<?php

namespace App\Shared\Idempotency;

use App\Shared\Exceptions\DomainException;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ensures retriable posting endpoints execute at most once per `Idempotency-Key`
 * (agent_docs/08_API_SPECIFICATION.md §1, AGENT_INSTRUCTIONS.md §7).
 *
 * Domain services may also call `run()` directly inside their own DB transaction so the
 * idempotency record commits atomically with the ledger rows.
 */
final class IdempotencyService
{
    public const HEADER = 'Idempotency-Key';

    /**
     * @template T
     *
     * @param  Closure(): array{status: int, body: array<string, mixed>}  $callback
     * @return array{status: int, body: array<string, mixed>, replayed: bool}
     */
    public function execute(string $key, string $operation, string $requestHash, ?int $userId, Closure $callback): array
    {
        $existing = IdempotencyKey::query()->where('key', $key)->lockForUpdate()->first();

        if ($existing !== null) {
            return $this->replay($existing, $operation, $requestHash);
        }

        try {
            $record = IdempotencyKey::query()->create([
                'key' => $key,
                'operation' => $operation,
                'request_hash' => $requestHash,
                'user_id' => $userId,
                'expires_at' => now()->addHours((int) config('pwms.idempotency_ttl_hours', 48)),
            ]);
        } catch (UniqueConstraintViolationException) {
            $existing = IdempotencyKey::query()->where('key', $key)->lockForUpdate()->firstOrFail();

            return $this->replay($existing, $operation, $requestHash);
        }

        $result = $callback();

        $record->update([
            'response_status' => $result['status'],
            'response_json' => $result['body'],
        ]);

        return [...$result, 'replayed' => false];
    }

    public static function hashRequest(Request $request): string
    {
        return hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());
    }

    /**
     * @return array{status: int, body: array<string, mixed>, replayed: bool}
     */
    private function replay(IdempotencyKey $record, string $operation, string $requestHash): array
    {
        if ($record->operation !== $operation || ! hash_equals($record->request_hash, $requestHash)) {
            throw new DomainException(
                'Idempotency-Key sudah digunakan untuk permintaan yang berbeda.',
                'IDEMPOTENCY_KEY_REUSED_WITH_DIFFERENT_PAYLOAD',
                409,
            );
        }

        if ($record->response_json === null || $record->response_status === null) {
            throw new DomainException(
                'Permintaan dengan Idempotency-Key ini sedang diproses.',
                'IDEMPOTENCY_KEY_IN_FLIGHT',
                409,
            );
        }

        return [
            'status' => $record->response_status,
            'body' => $record->response_json,
            'replayed' => true,
        ];
    }
}
