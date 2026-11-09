<?php

namespace App\Shared\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Writes audit entries for login, privileged actions, master changes, and postings.
 * Sensitive attributes are always stripped before persisting.
 */
final class AuditLogger
{
    /** @var list<string> */
    private const REDACTED_KEYS = ['password', 'password_confirmation', 'remember_token', 'token', 'current_password'];

    public function __construct(private readonly ?Request $request = null) {}

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
    ): AuditLog {
        $actor ??= $this->request?->user();

        return AuditLog::query()->create([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entity ? $this->entityType($entity) : null,
            'entity_id' => $entity?->getKey(),
            'before_json' => $before !== null ? $this->sanitize($before) : null,
            'after_json' => $after !== null ? $this->sanitize($after) : null,
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request ? Str::limit((string) $this->request->userAgent(), 500, '') : null,
        ]);
    }

    /**
     * Record an update with only the attributes that actually changed.
     *
     * @param  array<string, mixed>  $original
     */
    public function recordChanges(string $action, Model $entity, array $original): ?AuditLog
    {
        $changes = Arr::except($entity->getChanges(), ['updated_at']);
        if ($changes === []) {
            return null;
        }

        return $this->record($action, $entity, Arr::only($original, array_keys($changes)), $changes);
    }

    private function entityType(Model $entity): string
    {
        return $entity->getTable();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::REDACTED_KEYS, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
