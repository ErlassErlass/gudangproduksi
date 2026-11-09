<?php

namespace App\Shared\Inventory\Contracts;

use App\Shared\Inventory\DTO\PostInventoryCommand;

/**
 * Single authoritative entry point for all inventory balance and ledger mutations
 * (agent_docs/04_SYSTEM_ARCHITECTURE.md §5, AGENT_INSTRUCTIONS.md §4 and §7).
 *
 * Concrete implementation lands in Phase 3 (Inventory Engine) and must satisfy Gate A
 * before any downstream module (Procurement, Production, Vendor, Rental, Distribution) posts stock.
 */
interface InventoryPostingContract
{
    /**
     * Atomically validate dimensions, lock balance rows (`SELECT ... FOR UPDATE`), persist the
     * ledger header/lines, update balance projections and cost layers, record the idempotency
     * result and audit/outbox event, and return the posted transaction summary.
     *
     * @return array{id: int, document_no: string, status: string}
     */
    public function post(PostInventoryCommand $command): array;

    /**
     * Create and post a compensating reversal transaction linked to `$inventoryTransactionId`.
     * Posted transactions themselves are never edited or deleted.
     *
     * @return array{id: int, document_no: string, status: string, reversed_transaction_id: int}
     */
    public function reverse(int $inventoryTransactionId, string $idempotencyKey, int $userId, string $reason): array;
}
