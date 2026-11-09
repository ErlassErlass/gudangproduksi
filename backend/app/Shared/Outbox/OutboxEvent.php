<?php

namespace App\Shared\Outbox;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Transactional outbox record written inside the same DB transaction as domain state changes
 * (agent_docs/04_SYSTEM_ARCHITECTURE.md §5).
 */
class OutboxEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function record(string $aggregateType, int $aggregateId, string $eventType, array $payload): self
    {
        return self::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'payload_json' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
