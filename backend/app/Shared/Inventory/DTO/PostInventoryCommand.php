<?php

namespace App\Shared\Inventory\DTO;

use Carbon\CarbonInterface;

/**
 * Immutable posting command accepted by `InventoryPostingContract`.
 * Every stock-changing feature builds one of these; none mutates balances directly.
 */
final readonly class PostInventoryCommand
{
    /**
     * @param  non-empty-list<StockMovementLine>  $lines
     */
    public function __construct(
        public string $idempotencyKey,
        public string $transactionType,
        public string $sourceDocumentType,
        public int $sourceDocumentId,
        public int $postedByUserId,
        public array $lines,
        public ?CarbonInterface $transactionDate = null,
        public ?string $notes = null,
    ) {}
}
