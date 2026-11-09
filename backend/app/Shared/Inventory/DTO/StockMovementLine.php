<?php

namespace App\Shared\Inventory\DTO;

/**
 * Single stock movement line submitted to the Inventory Posting Service.
 * Quantity and unit cost are passed as decimal strings (never float) to preserve precision.
 */
final readonly class StockMovementLine
{
    public function __construct(
        public int $productId,
        public string $quantity,
        public ?int $fromLocationId = null,
        public ?int $toLocationId = null,
        public ?int $batchId = null,
        public ?int $productSerialId = null,
        public ?string $stockStatusFrom = null,
        public ?string $stockStatusTo = null,
        public ?string $unitCost = null,
        public ?int $consumeReservationId = null,
    ) {}
}
